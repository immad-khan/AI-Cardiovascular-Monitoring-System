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

def run_inference(raw_data_str):
    try:
        # 1. Parse raw data
        # Assuming comma separated values
        raw_signal = np.array([float(x) for x in raw_data_str.split(',')])
        
        start_time = time.time()
        
        # 2. Preprocess
        segments, r_peaks = preprocess_raw_ecg(raw_signal, fs=FS, from_ads1292r=True)
        
        if len(segments) == 0:
            return {
                "success": False,
                "message": "No heartbeats detected in the signal."
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
            # Reshape to (1, 187, 1) as expected by model
            input_data = seg.astype(np.float32).reshape(1, 187, 1)
            interpreter.set_tensor(input_details[0]['index'], input_data)
            interpreter.invoke()
            
            output_data = interpreter.get_tensor(output_details[0]['index'])[0]
            pred_class = np.argmax(output_data)
            confidence = float(output_data[pred_class])
            
            results.append(pred_class)
            confidences.append(confidence)
            
        # 5. Aggregate Results
        # Final prediction is the most frequent class detected
        final_class_id = int(max(set(results), key=results.count))
        avg_confidence = float(np.mean(confidences))
        
        # 6. Calculate Heart Rate (HR) from R-Peaks
        # HR = (60 * FS) / average_RR_interval
        if len(r_peaks) > 1:
            rr_intervals = np.diff(r_peaks)
            avg_rr = np.mean(rr_intervals)
            heart_rate = (60 * FS) / avg_rr
        else:
            heart_rate = 0.0 # Not enough beats
            
        # 7. Derived Labels (Tachy/Brady)
        status_label = CLASS_NAMES[final_class_id]
        if heart_rate > 100:
            status_label += " (Tachycardia)"
        elif heart_rate < 60 and heart_rate > 0:
            status_label += " (Bradycardia)"
            
        inference_time = (time.time() - start_time) * 1000 # ms
        
        return {
            "success": True,
            "predictionClass": status_label,
            "confidenceScore": avg_confidence,
            "heartRate": round(heart_rate, 1),
            "inference_time_ms": int(inference_time),
            "class_counts": {CLASS_NAMES[i]: results.count(i) for i in set(results)}
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
        # Expecting raw data as a single string argument
        # PHP: shell_exec("python predict.py 'value1,value2,value3'")
        result = run_inference(sys.argv[1])
        print(json.dumps(result))
