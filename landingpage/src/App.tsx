import { useState, useEffect, useRef, type ChangeEvent, type FormEvent, type MouseEvent } from 'react';

type IconName =
  | 'activity'
  | 'arrow'
  | 'brain'
  | 'chart'
  | 'check'
  | 'chevron'
  | 'clock'
  | 'device'
  | 'eye'
  | 'eye-off'
  | 'heart'
  | 'lock'
  | 'message'
  | 'play'
  | 'pulse'
  | 'shield'
  | 'star'
  | 'upload'
  | 'user'
  | 'wifi';

function Icon({ name, size = 22 }: { name: IconName; size?: number }) {
  const common = {
    width: size,
    height: size,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.7,
    strokeLinecap: 'round' as const,
    strokeLinejoin: 'round' as const,
    'aria-hidden': true,
  };

  switch (name) {
    case 'activity':
      return <svg {...common}><path d="M3 12h4l2-7 4 14 2-7h6" /></svg>;
    case 'arrow':
      return <svg {...common}><path d="M5 12h13" /><path d="m13 6 6 6-6 6" /></svg>;
    case 'brain':
      return <svg {...common}><path d="M9.5 4.5A3.5 3.5 0 0 0 6 8v.3A3.2 3.2 0 0 0 4 11.2a3.2 3.2 0 0 0 2 2.9v.4a3.5 3.5 0 0 0 5 3.1" /><path d="M14.5 4.5A3.5 3.5 0 0 1 18 8v.3a3.2 3.2 0 0 1 2 2.9 3.2 3.2 0 0 1-2 2.9v.4a3.5 3.5 0 0 1-5 3.1" /><path d="M12 5v14M8 9.5h4M12 14.5h4" /></svg>;
    case 'chart':
      return <svg {...common}><path d="M3 3v18h18" /><path d="M7 16l4-6 4 4 5-8" /></svg>;
    case 'check':
      return <svg {...common}><path d="m5 12 4 4L19 6" /></svg>;
    case 'chevron':
      return <svg {...common}><path d="m6 9 6 6 6-6" /></svg>;
    case 'clock':
      return <svg {...common}><circle cx="12" cy="12" r="8.5" /><path d="M12 7v5l3.5 2" /></svg>;
    case 'device':
      return <svg {...common}><rect x="5" y="2" width="14" height="20" rx="2" /><path d="M12 18h.01" /></svg>;
    case 'eye':
      return <svg {...common}><path d="M1 12s4.5-8 11-8 11 8 11 8-4.5 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>;
    case 'eye-off':
      return <svg {...common}><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" /><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" /><line x1="1" y1="1" x2="23" y2="23" /></svg>;
    case 'heart':
      return <svg {...common}><path d="M20.8 8.8c0 5-8.8 10-8.8 10s-8.8-5-8.8-10A4.7 4.7 0 0 1 12 6.3a4.7 4.7 0 0 1 8.8 2.5Z" /></svg>;
    case 'lock':
      return <svg {...common}><rect x="5" y="10" width="14" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" /></svg>;
    case 'message':
      return <svg {...common}><path d="M4 5.5h16v10H9l-5 3v-13Z" /><path d="M8 9.5h8M8 13h5" /></svg>;
    case 'play':
      return <svg {...common}><circle cx="12" cy="12" r="9" /><path d="m10 8 6 4-6 4V8Z" fill="currentColor" stroke="none" /></svg>;
    case 'pulse':
      return <svg {...common}><path d="M3 12h3l2-4 3.3 8 2.3-5 1.4 2H21" /></svg>;
    case 'shield':
      return <svg {...common}><path d="M12 3 19 6v5c0 4.8-3 8.1-7 10-4-1.9-7-5.2-7-10V6l7-3Z" /><path d="m8.5 12 2.3 2.3 4.8-5" /></svg>;
    case 'star':
      return <svg {...common}><path d="M12 2l2.9 5.8 6.4.9-4.6 4.5 1.1 6.4L12 16.3 6.2 19.6l1.1-6.4-4.6-4.5 6.4-.9L12 2z" fill="currentColor" stroke="none" /></svg>;
    case 'upload':
      return <svg {...common}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><path d="m17 8-5-5-5 5" /><path d="M12 3v12" /></svg>;
    case 'wifi':
      return <svg {...common}><path d="M5 12.55a11 11 0 0 1 14 0" /><path d="M8.5 16.25a6 6 0 0 1 7 0" /><circle cx="12" cy="20" r=".5" fill="currentColor" /></svg>;
    default:
      return <svg {...common}><circle cx="12" cy="8" r="3.1" /><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3" /></svg>;
  }
}

function Brand({ inverse = false }: { inverse?: boolean }) {
  return (
    <span className={`brand ${inverse ? 'brand-inverse' : ''}`}>
      <span className="brand-symbol" aria-hidden="true">
        <span /><span /><span />
      </span>
      <span className="brand-word">digihealth</span>
    </span>
  );
}

/* ─── Animated counter hook ─── */
function useCounter(end: number, duration = 2000) {
  const [count, setCount] = useState(0);
  const ref = useRef<HTMLDivElement>(null);
  const started = useRef(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && !started.current) {
          started.current = true;
          const start = performance.now();
          const tick = (now: number) => {
            const progress = Math.min((now - start) / duration, 1);
            setCount(Math.floor(progress * end));
            if (progress < 1) requestAnimationFrame(tick);
          };
          requestAnimationFrame(tick);
        }
      },
      { threshold: 0.3 }
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, [end, duration]);

  return { count, ref };
}

/* ─── FAQ Accordion item ─── */
function FaqItem({ q, a }: { q: string; a: string }) {
  const [open, setOpen] = useState(false);
  return (
    <div className={`faq-item ${open ? 'faq-open' : ''}`}>
      <button className="faq-question" onClick={() => setOpen(!open)} aria-expanded={open}>
        <span>{q}</span>
        <Icon name="chevron" size={18} />
      </button>
      <div className="faq-answer"><p>{a}</p></div>
    </div>
  );
}

/* ─── Types ─── */
type SubscriptionData = {
  name: string;
  email: string;
  phone: string;
  age: string;
  gender: string;
  note: string;
};

const initialSubscription: SubscriptionData = {
  name: '',
  email: '',
  phone: '',
  age: '',
  gender: '',
  note: '',
};

/* ─── Sign-In View ─── */
function SignInView({ onBack }: { onBack: () => void }) {
  const [showPassword, setShowPassword] = useState(false);

  return (
    <main className="signin-page">
      <div className="signin-image" aria-hidden="true" />
      <div className="signin-wash" aria-hidden="true" />
      <header className="signin-header">
        <button className="brand-button" onClick={onBack} aria-label="Return to home">
          <Brand />
        </button>
        <button className="text-button" onClick={onBack}>← Back to home</button>
      </header>
      <section className="signin-content" aria-labelledby="signin-title">
        <div className="signin-mark"><Icon name="heart" size={26} /></div>
        <h1 id="signin-title">Sign In</h1>
        <p className="signin-intro">Personalized Portal for Admin, Doctor &amp; Patients</p>
        
        {(() => {
          const params = new URLSearchParams(window.location.search);
          const status = params.get('status');
          const type = params.get('type') || 'error';
          if (status) {
            return (
              <div className="form-notice" style={{ marginBottom: 20, textAlign: 'left', borderColor: type === 'error' ? '#e0533d' : 'var(--teal)' }}>
                {status}
              </div>
            );
          }
          return null;
        })()}

        <form className="signin-form" action="../index.php" method="POST">
          <label className="signin-input">
            <input type="text" name="username" placeholder="Email or Username" required />
            <span className="input-icon"><Icon name="user" size={18} /></span>
          </label>
          <label className="signin-input">
            <input type={showPassword ? 'text' : 'password'} name="password" placeholder="Password" required />
            <span className="input-icon input-icon-toggle" role="button" tabIndex={0} aria-label={showPassword ? 'Hide password' : 'Show password'} onClick={() => setShowPassword(v => !v)} onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setShowPassword(v => !v); } }}>
              <Icon name={showPassword ? 'eye-off' : 'eye'} size={18} />
            </span>
          </label>
          <div className="access-note">
            <p>Authorized access only for:</p>
            <div className="access-badges">
              <span className="badge-admin">ADMINS</span>
              <span className="badge-doctor">DOCTORS</span>
              <span className="badge-patient">PATIENTS</span>
            </div>
          </div>
          <button className="primary-button full-button signin-cta" type="submit">SIGN IN</button>
        </form>
        <p className="signin-footnote">Contact your administrator for portal access</p>
      </section>
      <p className="signin-copyright">Capital University of Science and Technology, Islamabad © 2026</p>
    </main>
  );
}

/* ─── Subscription Modal ─── */
function SubscriptionModal({ onClose }: { onClose: () => void }) {
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [form, setForm] = useState<SubscriptionData>(initialSubscription);
  const [paymentFile, setPaymentFile] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const fileInputRef = useRef<HTMLInputElement>(null);

  function updateField(field: keyof SubscriptionData, value: string) {
    setForm((c) => ({ ...c, [field]: value }));
  }

  function handleFile(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0] ?? null;
    setPaymentFile(file);
  }

  function handleStep1(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setStep(2);
  }

  async function handleStep2(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!paymentFile) { setError('Please upload a payment screenshot.'); return; }
    setSubmitting(true);
    setError('');
    try {
      const fd = new FormData();
      fd.append('name', form.name);
      fd.append('email', form.email);
      fd.append('phone', form.phone);
      fd.append('age', form.age);
      fd.append('gender', form.gender);
      fd.append('note', form.note);
      fd.append('payment_screenshot', paymentFile);
      const res = await fetch('../../api/submit_subscription.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.status === 'success') { setStep(3); }
      else { setError(data.message || 'Submission failed. Please try again.'); }
    } catch { setError('Network error. Please try again.'); }
    finally { setSubmitting(false); }
  }

  return (
    <div className="modal-backdrop" role="presentation" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <section className="subscription-modal" role="dialog" aria-modal="true" aria-labelledby="subscribe-title">
        <div className="modal-heading">
          <div>
            <p className="eyebrow">Start your membership</p>
            <h2 id="subscribe-title">Bring digihealth home.</h2>
          </div>
          <button className="close-button" type="button" onClick={onClose} aria-label="Close">
            <span /><span />
          </button>
        </div>

        {/* Step indicator */}
        <div className="step-indicator">
          <div className={`si ${step >= 1 ? 'si-active' : ''}`}><span>1</span> Your info</div>
          <div className="si-line" />
          <div className={`si ${step >= 2 ? 'si-active' : ''}`}><span>2</span> Payment</div>
          <div className="si-line" />
          <div className={`si ${step >= 3 ? 'si-active' : ''}`}><span>3</span> Done</div>
        </div>

        {step === 1 && (
          <form className="subscription-form" onSubmit={handleStep1}>
            <p className="form-subtitle">Tell us about yourself so we can set up your account.</p>
            <div className="field-grid">
              <label className="wide-field"><span>Full name</span><input value={form.name} onChange={(e) => updateField('name', e.target.value)} placeholder="Your full name" required /></label>
              <label><span>Email address</span><input type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} placeholder="you@example.com" required /></label>
              <label><span>Phone number</span><input type="tel" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} placeholder="+92 300 000 0000" required /></label>
              <label><span>Age</span><input type="number" min="1" max="120" value={form.age} onChange={(e) => updateField('age', e.target.value)} placeholder="Age" required /></label>
              <label>
                <span>Gender</span>
                <select value={form.gender} onChange={(e) => updateField('gender', e.target.value)} required>
                  <option value="" disabled>Select one</option>
                  <option>Female</option>
                  <option>Male</option>
                  <option>Non-binary</option>
                  <option>Prefer not to say</option>
                </select>
              </label>
              <label className="wide-field"><span>Note for the care team <em>Optional</em></span><textarea value={form.note} onChange={(e) => updateField('note', e.target.value)} placeholder="Share anything we should know" rows={3} /></label>
            </div>
            <div className="modal-actions">
              <p><Icon name="shield" size={16} /> Your information is used only to set up your membership.</p>
              <button className="primary-button" type="submit">Continue to payment <Icon name="arrow" size={18} /></button>
            </div>
          </form>
        )}

        {step === 2 && (
          <form className="subscription-form" onSubmit={handleStep2}>
            <p className="form-subtitle">Transfer the subscription fee and upload a screenshot as proof.</p>
            <div className="bank-details">
              <span className="bank-label">Transfer USD 1.00 / month to</span>
              <strong>Digihealth Care</strong>
              <span>Bank: HBL — Account: 0123 4567 8901 2345</span>
              <small>Use your full name as the transfer reference.</small>
            </div>
            <div className="field-grid" style={{ marginTop: 20 }}>
              <label className="upload-field wide-field">
                <span>Payment screenshot</span>
                <input ref={fileInputRef} type="file" accept="image/*" onChange={handleFile} required />
                <span className="upload-control"><Icon name="upload" size={17} /> {paymentFile?.name || 'Click to upload your payment screenshot'}</span>
              </label>
            </div>
            {error && <p className="form-notice" style={{ color: '#ff5252', marginTop: 12 }}>{error}</p>}
            <div className="modal-actions">
              <button className="outline-button" type="button" onClick={() => setStep(1)}>← Back</button>
              <button className="primary-button" type="submit" disabled={submitting}>{submitting ? 'Submitting...' : 'Submit membership'} {!submitting && <Icon name="arrow" size={18} />}</button>
            </div>
          </form>
        )}

        {step === 3 && (
          <div className="thank-you" role="status">
            <div className="thank-you-icon"><Icon name="check" size={28} /></div>
            <p className="eyebrow">Payment received for review</p>
            <h3>Thank you, {form.name.split(' ')[0] || 'there'}!</h3>
            <p>Your information and payment screenshot have been shared with our care team. We will confirm your membership at <strong>{form.email}</strong> within 24 hours.</p>
            <button className="primary-button" type="button" onClick={onClose}>Return to digihealth</button>
          </div>
        )}
      </section>
    </div>
  );
}

/* ─── HERO ─── */
function Hero({ onSignIn }: { onSignIn: (e: MouseEvent<HTMLAnchorElement>) => void }) {
  return (
    <section className="hero" id="top">
      <div className="hero-background" aria-hidden="true" />
      <header className="site-header shell">
        <button className="brand-button" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })} aria-label="digihealth home">
          <Brand />
        </button>
        <nav className="desktop-nav" aria-label="Primary navigation">
          <a href="#how-it-works">How it works</a>
          <a href="#features">Features</a>
          <a href="#insights">The platform</a>
          <a href="#membership">Membership</a>
        </nav>
        <a className="header-signin" href="#signin" onClick={onSignIn}>Sign in <Icon name="arrow" size={17} /></a>
      </header>
      <div className="hero-content shell">
        <div className="hero-copy">
          <p className="eyebrow reveal reveal-one">At-home cardiac intelligence</p>
          <h1 className="reveal reveal-two">Your heart,<br /><em>understood.</em></h1>
          <p className="hero-description reveal reveal-three">Connect your ECG device at home, see what your heart is telling you, and stay close to better care — all through digihealth.</p>
          <div className="hero-actions reveal reveal-four">
            <a className="primary-button" href="#how-it-works">See how it works <Icon name="arrow" size={18} /></a>
            <a className="quiet-link" href="#membership">Explore membership <Icon name="arrow" size={16} /></a>
          </div>
          <div className="hero-caption reveal reveal-four"><span className="caption-line" /> Built for everyday clarity, not clinical complexity.</div>
        </div>
      </div>
      <a className="scroll-cue" href="#how-it-works"><span>Scroll to explore</span><span className="scroll-line" /></a>
    </section>
  );
}

/* ─── TRUST BAR ─── */
function TrustBar() {
  const s2 = useCounter(98, 1600);
  const s3 = useCounter(24, 1400);
  const s4 = useCounter(10000, 2000);

  return (
    <section className="trust-bar">
      <div className="section-shell trust-grid">
        <div ref={s2.ref}><strong>{s2.count}%</strong><span>AI accuracy rate</span></div>
        <div ref={s3.ref}><strong>{s3.count}/7</strong><span>Real-time monitoring</span></div>
        <div ref={s4.ref}><strong>{s4.count.toLocaleString()}+</strong><span>ECG readings analyzed</span></div>
      </div>
    </section>
  );
}

/* ─── HOW IT WORKS ─── */
function HowItWorks() {
  const steps = [
    { number: '01', icon: 'device' as IconName, title: 'Connect your device', text: 'Use any compatible ECG device at home. Simply connect it and take a reading in a few quiet minutes — no clinic visit needed.' },
    { number: '02', icon: 'wifi' as IconName, title: 'Data syncs automatically', text: 'Your ECG reading is securely transmitted to digihealth where our AI engine analyzes rhythm patterns, heart rate, SDNN and RMSSD.' },
    { number: '03', icon: 'brain' as IconName, title: 'AI generates insights', text: 'Advanced algorithms detect anomalies like bradycardia or irregular rhythms, giving you clear, actionable context — not confusing medical jargon.' },
    { number: '04', icon: 'message' as IconName, title: 'Connect with your doctor', text: 'Review your analytics, ask the AI assistant questions, or start a conversation directly with your assigned doctor for professional guidance.' },
  ];

  return (
    <section className="how-section" id="how-it-works">
      <div className="section-shell">
        <div className="how-header">
          <div className="section-intro">
            <p className="eyebrow">A clearer rhythm of care</p>
            <h2>From a reading<br />to a <em>next step.</em></h2>
            <p>Healthcare should not stop when you leave the clinic. digihealth brings the right signals and the right people closer to home.</p>
          </div>
        </div>
        <div className="step-grid-v2">
          {steps.map((step) => (
            <article className="step-v2" key={step.number}>
              <div className="step-icon-wrap"><Icon name={step.icon} size={24} /></div>
              <div className="step-number">{step.number}</div>
              <h3>{step.title}</h3>
              <p>{step.text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─── FEATURES SECTION ─── */
function FeaturesSection() {
  const features = [
    { icon: 'pulse' as IconName, title: 'Heart Rate Monitoring', desc: 'Track your heart rate trends over time with precise measurements from your home ECG device, displayed in beautiful interactive charts.' },
    { icon: 'activity' as IconName, title: 'SDNN & RMSSD Analytics', desc: 'Monitor heart rate variability metrics — SDNN and RMSSD — that reveal your autonomic nervous system health and stress response patterns.' },
    { icon: 'brain' as IconName, title: 'AI-Powered Predictions', desc: 'Our AI analyzes every reading to detect conditions like bradycardia, tachycardia, and irregular rhythms including possible AFib.' },
    { icon: 'chart' as IconName, title: 'Visual Analytics Dashboard', desc: 'Access your complete health history through an intuitive dashboard with trends, waveform history, and signal quality indicators.' },
    { icon: 'message' as IconName, title: 'Doctor Chat & Consultation', desc: 'Connect directly with your assigned healthcare provider. Share readings, discuss concerns, and receive professional guidance from home.' },
    { icon: 'shield' as IconName, title: 'AI Health Assistant', desc: 'Get instant answers to your health questions from our AI assistant, trained on cardiac care guidelines and your personal health data.' },
  ];

  return (
    <section className="features-section" id="features">
      <div className="section-shell">
        <div className="features-header">
          <p className="eyebrow">Everything you need</p>
          <h2>Powerful features,<br /><em>simple experience.</em></h2>
          <p>Every tool is designed to help you understand your heart better — from raw data to clear insights.</p>
        </div>
        <div className="features-grid">
          {features.map((f) => (
            <article className="feature-card" key={f.title}>
              <div className="feature-icon"><Icon name={f.icon} size={24} /></div>
              <h3>{f.title}</h3>
              <p>{f.desc}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─── INSIGHTS / ANALYTICS VISUAL ─── */
function InsightsSection() {
  return (
    <section className="insights-section" id="insights">
      <div className="section-shell insights-grid">
        <div className="analytics-visual" aria-label="Illustration of a digihealth heart analytics dashboard">
          <div className="visual-topline"><span><span className="live-dot" /> Live reading</span><span>Today, 09:42</span></div>
          <div className="visual-heading"><div><p>Heart rate</p><strong>72 <small>bpm</small></strong></div><span className="normal-status">Within range</span></div>
          <div className="wave-area">
            <div className="wave-labels"><span>80</span><span>70</span><span>60</span></div>
            <svg viewBox="0 0 600 180" preserveAspectRatio="none" aria-hidden="true">
              <path className="wave-shadow" d="M0 94 C25 94 28 92 48 94 S68 95 78 94 L91 94 100 91 108 94 117 94 128 90 136 94 154 94 C176 94 180 96 197 94 S221 93 237 94 L252 94 260 98 268 92 277 94 288 94 C311 94 317 95 335 94 S356 92 373 94 L389 94 398 88 407 100 416 94 425 94 436 90 444 94 C463 94 472 96 489 94 S516 92 535 94 L553 94 563 88 570 99 578 94 600 94" />
              <path className="wave-line" d="M0 94 C25 94 28 92 48 94 S68 95 78 94 L91 94 100 91 108 94 117 94 128 90 136 94 154 94 C176 94 180 96 197 94 S221 93 237 94 L252 94 260 98 268 92 277 94 288 94 C311 94 317 95 335 94 S356 92 373 94 L389 94 398 88 407 100 416 94 425 94 436 90 444 94 C463 94 472 96 489 94 S516 92 535 94 L553 94 563 88 570 99 578 94 600 94" />
            </svg>
          </div>
          <div className="metrics-row">
            <div><span>SDNN</span><strong>84.7 <small>ms</small></strong><i>+8.2%</i></div>
            <div><span>RMSSD</span><strong>118.9 <small>ms</small></strong><i>+4.6%</i></div>
            <div><span>AI reading</span><strong>Normal</strong><i className="teal-text">Stable rhythm</i></div>
          </div>
          <div className="visual-bottomline"><span>Last 7 days</span><span>View full analytics <Icon name="arrow" size={15} /></span></div>
        </div>
        <div className="insights-copy">
          <p className="eyebrow">The detail behind the data</p>
          <h2>Know your patterns,<br /><em>not just your pulse.</em></h2>
          <p>Every reading becomes a little more useful. Follow your heart rate, SDNN and RMSSD over time, then see AI-generated context that helps you know when to pause, ask, or act.</p>
          <ul className="feature-list">
            <li><span><Icon name="brain" size={21} /></span><div><strong>AI assisted predictions</strong><small>Clear signals from complex readings — never a substitute for medical care.</small></div></li>
            <li><span><Icon name="message" size={21} /></span><div><strong>Doctor conversations</strong><small>Keep your history close when you need a human perspective.</small></div></li>
            <li><span><Icon name="clock" size={21} /></span><div><strong>Longitudinal analytics</strong><small>Watch how your heart health changes, one reading at a time.</small></div></li>
          </ul>
        </div>
      </div>
    </section>
  );
}

/* ─── TESTIMONIALS ─── */
function TestimonialsSection() {
  const testimonials = [
    { name: 'Ahmed K.', role: 'Patient, Islamabad', text: 'I was always anxious about my heart. digihealth lets me check from home and the AI predictions give me peace of mind. My doctor can see everything too.', rating: 5 },
    { name: 'Dr. Sarah M.', role: 'Cardiologist', text: 'The SDNN and RMSSD data combined with AI analysis helps me make better decisions for my patients. It is like having a 24/7 monitoring assistant.', rating: 5 },
    { name: 'Fatima R.', role: 'Patient, Lahore', text: 'For just $1 a month I get real ECG insights at home. The doctor chat feature saved me an unnecessary hospital visit. Highly recommended!', rating: 5 },
  ];

  return (
    <section className="testimonials-section">
      <div className="section-shell">
        <div className="testimonials-header">
          <p className="eyebrow">What people say</p>
          <h2>Trusted by patients<br /><em>and doctors.</em></h2>
        </div>
        <div className="testimonials-grid">
          {testimonials.map((t) => (
            <article className="testimonial-card" key={t.name}>
              <div className="stars">{Array.from({ length: t.rating }, (_, i) => <Icon key={i} name="star" size={14} />)}</div>
              <p className="testimonial-text">"{t.text}"</p>
              <div className="testimonial-author">
                <div className="author-avatar"><Icon name="user" size={18} /></div>
                <div><strong>{t.name}</strong><small>{t.role}</small></div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

/* ─── MEMBERSHIP ─── */
function MembershipSection({ onSubscribe }: { onSubscribe: () => void }) {
  return (
    <section className="membership-section section-shell" id="membership">
      <div className="membership-heading">
        <div>
          <p className="eyebrow">One plan. No noise.</p>
          <h2>A little more insight<br /><em>for one dollar a month.</em></h2>
        </div>
      </div>
      <div className="membership-card">
        <div className="membership-card-top">
          <div className="plan-badge"><Icon name="heart" size={20} /></div>
          <h3>digihealth care</h3>
          <p className="plan-subtitle">For patients who want to stay informed at home.</p>
          <div className="plan-price"><strong>$1</strong><span>/ month</span></div>
        </div>
        <ul className="plan-features">
          <li><Icon name="check" size={17} /> Home ECG device readings</li>
          <li><Icon name="check" size={17} /> AI-powered heart analysis</li>
          <li><Icon name="check" size={17} /> Heart rate, SDNN & RMSSD tracking</li>
          <li><Icon name="check" size={17} /> Visual analytics dashboard</li>
          <li><Icon name="check" size={17} /> Chat with your doctor</li>
          <li><Icon name="check" size={17} /> AI health assistant</li>
          <li><Icon name="check" size={17} /> ECG waveform history</li>
        </ul>
        <button className="primary-button plan-cta" onClick={onSubscribe}>Subscribe now <Icon name="arrow" size={18} /></button>
      </div>
      <p className="membership-note"><Icon name="lock" size={15} /> Payment is reviewed securely by our care team. Medical decisions should always be made with a qualified professional.</p>
    </section>
  );
}

/* ─── FAQ ─── */
function FaqSection() {
  const faqs = [
    { q: 'What ECG devices are compatible with digihealth?', a: 'digihealth works with most single-lead and multi-lead portable ECG devices that can transmit data digitally. Contact our team for a full compatibility list.' },
    { q: 'How does the AI prediction work?', a: 'Our AI model analyzes your ECG waveform, heart rate, SDNN, and RMSSD values to detect patterns associated with conditions like bradycardia, tachycardia, and irregular rhythms. It provides a reading result along with a signal quality indicator.' },
    { q: 'Can I chat with a real doctor?', a: 'Yes! Every subscriber gets access to the doctor chat feature where you can message your assigned healthcare provider, share readings, and get professional medical guidance.' },
    { q: 'Is my health data secure?', a: 'Absolutely. All data is encrypted in transit and at rest. We follow strict privacy-by-design principles and your information is never shared with third parties.' },
    { q: 'How do I subscribe and pay?', a: 'Click the Subscribe button, fill in your personal details, transfer $1 to our bank account, upload a screenshot of the payment, and our team will activate your membership within 24 hours.' },
    { q: 'What happens after I subscribe?', a: 'Once your payment is verified, you will receive login credentials via email. You can then connect your ECG device, take readings, view your analytics dashboard, chat with doctors, and use the AI assistant.' },
  ];

  return (
    <section className="faq-section" id="faq">
      <div className="section-shell">
        <div className="faq-header">
          <p className="eyebrow">Common questions</p>
          <h2>Everything you need<br /><em>to know.</em></h2>
        </div>
        <div className="faq-list">
          {faqs.map((f) => <FaqItem key={f.q} q={f.q} a={f.a} />)}
        </div>
      </div>
    </section>
  );
}

/* ─── CTA SECTION ─── */
function CtaSection({ onSubscribe, onSignIn }: { onSubscribe: () => void; onSignIn: () => void }) {
  return (
    <section className="cta-section">
      <div className="section-shell cta-content">
        <p className="eyebrow">Ready to start?</p>
        <h2>Take control of your<br /><em>heart health today.</em></h2>
        <p>Join hundreds of patients who monitor their cardiac health from the comfort of home.</p>
        <div className="cta-actions">
          <button className="primary-button" onClick={onSubscribe}>Subscribe for $1/month <Icon name="arrow" size={18} /></button>
          <button className="outline-button" onClick={onSignIn}>Sign in to portal <Icon name="arrow" size={18} /></button>
        </div>
      </div>
    </section>
  );
}

/* ─── FOOTER ─── */
function Footer({ onSignIn }: { onSignIn: () => void }) {
  return (
    <footer className="site-footer">
      <div className="section-shell footer-top">
        <div><Brand inverse /><p>Personal cardiac insight,<br />closer to home.</p></div>
        <div className="footer-links">
          <a href="#how-it-works">How it works</a>
          <a href="#features">Features</a>
          <a href="#insights">The platform</a>
          <a href="#membership">Membership</a>
          <a href="#faq">FAQ</a>
          <button onClick={onSignIn}>Sign in</button>
        </div>
        <a className="footer-arrow" href="#top" aria-label="Back to top"><Icon name="arrow" size={22} /></a>
      </div>
      <div className="section-shell footer-bottom">
        <span>© 2026 digihealth. All rights reserved.</span>
        <span>For informational purposes only. Not a medical diagnosis.</span>
      </div>
    </footer>
  );
}

/* ─── APP ─── */
export default function App() {
  const [view, setView] = useState<'landing' | 'signin'>(() => window.location.hash === '#signin' ? 'signin' : 'landing');
  const [isSubscribeOpen, setIsSubscribeOpen] = useState(() => window.location.hash === '#subscribe');

  function openSignIn(e?: { preventDefault: () => void }) {
    e?.preventDefault();
    setView('signin');
    window.history.replaceState(null, '', '#signin');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function goHome() {
    setView('landing');
    window.history.replaceState(null, '', '#top');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  if (view === 'signin') {
    return <SignInView onBack={goHome} />;
  }

  return (
    <div className="app-shell">
      <Hero onSignIn={openSignIn} />
      <TrustBar />
      <HowItWorks />
      <FeaturesSection />
      <InsightsSection />
      <TestimonialsSection />
      <MembershipSection onSubscribe={() => setIsSubscribeOpen(true)} />
      <FaqSection />
      <CtaSection onSubscribe={() => setIsSubscribeOpen(true)} onSignIn={openSignIn} />
      <Footer onSignIn={openSignIn} />
      {isSubscribeOpen && <SubscriptionModal onClose={() => setIsSubscribeOpen(false)} />}
    </div>
  );
}
