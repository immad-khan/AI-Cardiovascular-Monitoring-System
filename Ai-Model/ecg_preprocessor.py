import numpy as np
from scipy.signal import butter, filtfilt, iirnotch, find_peaks


# ─────────────────────────────────────────────
#  CONSTANTS
# ─────────────────────────────────────────────
FS = 360            # Sampling frequency (MIT-BIH uses 360 Hz; ADS1292R typical = 250/500 Hz)
SEGMENT_LENGTH = 187  # Samples per heartbeat window (standard for MIT-BIH classification)
ADS1292R_VREF = 2.42   # Reference voltage of ADS1292R (volts)
ADS1292R_GAIN = 6      # PGA gain setting
ADC_BITS = 24          # 24-bit ADC resolution


def ads1292r_to_millivolts(raw_value: int) -> float:
    """
    Convert a raw 24-bit integer from ADS1292R to millivolts.
    """
    # Handle two's complement for negative values
    if raw_value >= (1 << 23):
        raw_value -= (1 << 24)
    
    voltage_mv = (raw_value / (2**23)) * (ADS1292R_VREF / ADS1292R_GAIN) * 1000.0
    return voltage_mv


def bandpass_filter(signal: np.ndarray, lowcut=0.5, highcut=40.0, fs=FS, order=4) -> np.ndarray:
    """
    Butterworth bandpass filter.
    """
    nyq = 0.5 * fs
    low = lowcut / nyq
    high = highcut / nyq
    b, a = butter(order, [low, high], btype='band')
    return filtfilt(b, a, signal)


def notch_filter(signal: np.ndarray, freq=50.0, fs=FS, quality=30) -> np.ndarray:
    """
    IIR Notch filter to remove power-line interference.
    """
    w0 = freq / (0.5 * fs)
    b, a = iirnotch(w0, quality)
    return filtfilt(b, a, signal)


def normalize_signal(signal: np.ndarray) -> np.ndarray:
    """
    Z-score normalization.
    """
    mean = np.mean(signal)
    std = np.std(signal)
    if std < 1e-8:
        return signal - mean
    return (signal - mean) / std


def detect_r_peaks(signal: np.ndarray, fs=FS) -> np.ndarray:
    """
    R-peak detector using scipy find_peaks.
    """
    min_distance = int(0.5 * fs)
    height_threshold = np.mean(signal) + 0.5 * np.std(signal)
    peaks, _ = find_peaks(signal, distance=min_distance, height=height_threshold)
    return peaks


def segment_around_r_peak(signal: np.ndarray, r_peak_idx: int,
                           segment_length=SEGMENT_LENGTH) -> np.ndarray:
    """
    Extract a fixed-length window centred on an R-peak.
    """
    half = segment_length // 2
    start = r_peak_idx - half
    end = r_peak_idx + (segment_length - half)
    
    if start < 0 or end > len(signal):
        return None
    
    return signal[start:end]


def preprocess_raw_ecg(raw_signal: np.ndarray, fs=FS,
                        from_ads1292r=False) -> list[np.ndarray]:
    """
    Full preprocessing pipeline.
    """
    if from_ads1292r:
        signal_mv = np.array([ads1292r_to_millivolts(int(s)) for s in raw_signal])
    else:
        signal_mv = raw_signal.astype(np.float64)

    signal_notched = notch_filter(signal_mv, freq=50.0, fs=fs)
    signal_filtered = bandpass_filter(signal_notched, lowcut=0.5, highcut=40.0, fs=fs)
    r_peaks = detect_r_peaks(signal_filtered, fs=fs)

    segments = []
    for r in r_peaks:
        seg = segment_around_r_peak(signal_filtered, r, SEGMENT_LENGTH)
        if seg is not None:
            seg_norm = normalize_signal(seg)
            segments.append(seg_norm)

    return segments, r_peaks
