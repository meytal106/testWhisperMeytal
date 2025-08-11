import { useState } from 'react';

function App() {
  const [username, setUsername] = useState('');
  const [otp, setOtp] = useState('');
  const [honeypot, setHoneypot] = useState('');
  const [step, setStep] = useState('username');
  const [message, setMessage] = useState('');

  const sendOtp = async () => {
    if (honeypot !== '') {
      setMessage('Bot detected');
      return;
    }

    try {
      const res = await fetch('http://localhost/home-test/send_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: username.trim(), honeypot })
      });

      const text = await res.text();
      console.log('Response text:', text);

      const data = JSON.parse(text);

      if (data.success) {
        setStep('otp');
        setMessage('קוד נשלח לכתובת המייל שהזנת');
      } else {
        setMessage(data.message || 'שגיאה בשליחת הקוד :( נסה בעוד 30 שניות');
      }
    } catch (err) {
      console.error('Error sending OTP:', err);
      setMessage('Communication error');
    }
  };

  const verifyOtp = async () => {
    try {
      const res = await fetch('http://localhost/home-test/verify_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: username.trim(), otp: otp.trim() })
      });

      const text = await res.text();
      console.log('Response text:', text);

      const data = JSON.parse(text);

      if (data.success) {
        setMessage(`Login successful! Token: ${data.token}`);
      } else {
        setMessage(data.message || 'Invalid OTP');
      }
    } catch (err) {
      console.error('Error verifying OTP:', err);
      setMessage('Communication error');
    }
  };

  return (
    <div style={{ maxWidth: 400, margin: 'auto', padding: '2rem' }}>
      <h2>עמוד ההתחברות</h2>

      <input
        type="text"
        placeholder="שם משתמש או כתובת מייל"
        value={username}
        onChange={(e) => setUsername(e.target.value)}
      /><br /><br />

      {/* Honeypot field – hidden */}
      <input
        type="text"
        name="email2"
        value={honeypot}
        onChange={(e) => setHoneypot(e.target.value)}
        style={{ display: 'none' }}
        autoComplete="off"
      />

      {step === 'username' && (
        <button onClick={sendOtp}>שלח קוד</button>
      )}

      {step === 'otp' && (
        <>
          <input
            type="text"
            placeholder="הכנס את הקוד שהתקבל"
            value={otp}
            onChange={(e) => setOtp(e.target.value)}
          /><br /><br />
          <button onClick={verifyOtp}>אישור</button>
        </>
      )}

      <p>{message}</p>
    </div>
  );
}

export default App;
