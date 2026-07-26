import sys
import json
import numpy as np
import time
try:
    # pyrefly: ignore [missing-import]
    import tflite_runtime.interpreter as tflite
except ImportError:
    try:
        # pyrefly: ignore [missing-import]
        import tensorflow.lite as tflite
    except ImportError:
        print(json.dumps({"success": False, "message": "TFLite Runtime not found. Please install tflite-runtime or tensorflow."}))
        sys.exit(1)
from ecg_preprocessor import preprocess_raw_ecg, FS
import os

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "ecg_model.tflite")
CLASS_NAMES = {
    0: "Normal",
    1: "Supraventricular",
    2: "Ventricular",
    3: "Fusion",
    4: "Unknown/Paced"
}

def calculate_hrv(r_peaks: np.ndarray, fs: float) -> dict:
    """
    Calculate Heart Rate Variability metrics from R-peak positions.
    
    - SDNN: Standard deviation of NN intervals. Normal >50 ms.
            Low SDNN = reduced autonomic function / high stress.
    - RMSSD: Root mean square of successive differences.
             Reflects parasympathetic (vagal) activity.
             Normal >20 ms. Low = fatigue, poor recovery.
    """
    if len(r_peaks) < 3:
        return {"hrv_sdnn": None, "hrv_rmssd": None}

    # Convert R-peak sample indices to time intervals in milliseconds
    rr_intervals_ms = np.diff(r_peaks) / fs * 1000.0

    # Remove physiologically implausible RR intervals (< 300 ms or > 2000 ms)
    rr_intervals_ms = rr_intervals_ms[(rr_intervals_ms >= 300) & (rr_intervals_ms <= 2000)]

    if len(rr_intervals_ms) < 2:
        return {"hrv_sdnn": None, "hrv_rmssd": None}

    sdnn = float(np.std(rr_intervals_ms, ddof=1))
    successive_diffs = np.diff(rr_intervals_ms)
    rmssd = float(np.sqrt(np.mean(successive_diffs ** 2)))

    return {
        "hrv_sdnn": round(sdnn, 2),
        "hrv_rmssd": round(rmssd, 2)
    }


def detect_arrhythmia_flags(r_peaks: np.ndarray, fs: float, heart_rate: float) -> list:
    """
    Rule-based arrhythmia flag detection using RR interval analysis.
    Returns a list of flag strings describing detected anomalies.
    """
    flags = []

    if len(r_peaks) < 3:
        return flags

    rr_intervals_ms = np.diff(r_peaks) / fs * 1000.0
    rr_intervals_ms = rr_intervals_ms[(rr_intervals_ms >= 300) & (rr_intervals_ms <= 2000)]

    if len(rr_intervals_ms) < 2:
        return flags

    mean_rr = np.mean(rr_intervals_ms)
    std_rr = np.std(rr_intervals_ms)

    # 1. Irregular rhythm — high RR variability (possible AFib)
    # Coefficient of Variation > 10% suggests irregular rhythm
    cv = (std_rr / mean_rr) * 100 if mean_rr > 0 else 0
    if cv > 15:
        flags.append("Irregular Rhythm (possible AFib)")
    elif cv > 10:
        flags.append("Mildly Irregular Rhythm")

    # 2. Ectopic / premature beats — RR interval significantly shorter than mean
    # A beat is "early" if its RR is < 80% of the mean (premature depolarization)
    ectopic_count = int(np.sum(rr_intervals_ms < (0.80 * mean_rr)))
    if ectopic_count >= 2:
        flags.append(f"Ectopic Beats Detected ({ectopic_count})")
    elif ectopic_count == 1:
        flags.append("Single Ectopic Beat")

    # 3. Long pauses — RR interval > 150% of mean (missed beat / high-degree block)
    pauses = int(np.sum(rr_intervals_ms > (1.5 * mean_rr)))
    if pauses >= 1:
        flags.append(f"Long Pause Detected ({pauses})")

    # 4. Tachycardia / Bradycardia (supplemental to base label)
    if heart_rate > 150:
        flags.append("Severe Tachycardia (>150 BPM)")
    elif heart_rate > 100:
        flags.append("Tachycardia (>100 BPM)")
    elif heart_rate < 40 and heart_rate > 0:
        flags.append("Severe Bradycardia (<40 BPM)")
    elif heart_rate < 60 and heart_rate > 0:
        flags.append("Bradycardia (<60 BPM)")

    return flags


def run_inference(raw_data_str):
    try:
        # 1. Parse raw data (comma-separated values)
        raw_signal = np.array([float(x) for x in raw_data_str.split(',')])
        
        start_time = time.time()
        
        # 2. Preprocess — now returns (segments, r_peaks, sqi)
        segments, r_peaks, signal_quality = preprocess_raw_ecg(raw_signal, fs=FS, from_ads1292r=True)
        
        if len(segments) == 0:
            return {
                "success": False,
                "message": "No heartbeats detected in the signal.",
                "signal_quality": signal_quality
            }
            
        # 3. Load TFLite Model
        interpreter = tflite.Interpreter(model_path=MODEL_PATH)
        interpreter.allocate_tensors()
        
        input_details = interpreter.get_input_details()
        output_details = interpreter.get_output_details()
        
        results = []
        confidences = []
        
        # 4. Predict each segment
        for seg in segments:
            input_data = seg.astype(np.float32).reshape(1, 187, 1)
            interpreter.set_tensor(input_details[0]['index'], input_data)
            interpreter.invoke()
            
            output_data = interpreter.get_tensor(output_details[0]['index'])[0]
            pred_class = np.argmax(output_data)
            confidence = float(output_data[pred_class])
            
            results.append(pred_class)
            confidences.append(confidence)
            
        # 5. Aggregate Results — most frequent class wins
        final_class_id = int(max(set(results), key=results.count))
        avg_confidence = float(np.mean(confidences))
        
        # 6. Calculate Heart Rate from R-Peaks
        if len(r_peaks) > 1:
            rr_intervals = np.diff(r_peaks)
            avg_rr = np.mean(rr_intervals)
            heart_rate = (60 * FS) / avg_rr
        else:
            heart_rate = 0.0

        # 7. HRV Metrics
        hrv = calculate_hrv(r_peaks, FS)

        # 8. Arrhythmia Flags (rule-based, on top of model classification)
        arrhythmia_flags = detect_arrhythmia_flags(r_peaks, FS, heart_rate)

        # 9. Build final label
        status_label = CLASS_NAMES[final_class_id]
        if heart_rate > 100:
            status_label += " (Tachycardia)"
        elif heart_rate < 60 and heart_rate > 0:
            status_label += " (Bradycardia)"

        # Add most critical flag to label if not already implied
        if arrhythmia_flags and "Tachycardia" not in status_label and "Bradycardia" not in status_label:
            # Append first flag as a hint
            status_label += f" + {arrhythmia_flags[0]}"
            
        inference_time = (time.time() - start_time) * 1000  # ms
        
        return {
            "success": True,
            "predictionClass": status_label,
            "confidenceScore": avg_confidence,
            "heartRate": round(heart_rate, 1),
            "inference_time_ms": int(inference_time),
            "class_counts": {CLASS_NAMES[i]: results.count(i) for i in set(results)},
            # New Phase 1 fields
            "hrv_sdnn": hrv["hrv_sdnn"],
            "hrv_rmssd": hrv["hrv_rmssd"],
            "signal_quality": signal_quality,
            "arrhythmia_flags": arrhythmia_flags
        }
        
    except Exception as e:
        return {
            "success": False,
            "message": str(e)
        }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "message": "No data provided."}))
    else:
        result = run_inference(sys.argv[1])
        print(json.dumps(result))
