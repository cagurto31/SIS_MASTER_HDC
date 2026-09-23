<style>
body { background:#0B1628 !important; min-height:100vh; }
.container { max-width:100% !important; padding:0 !important; min-height:100vh; display:flex !important; align-items:center; justify-content:center; position:relative; }
.auth-bg { position:fixed; inset:0; background:linear-gradient(135deg,#0B1628 0%,#0f2347 40%,#0d1f3c 70%,#0B1628 100%); z-index:0; }
.auth-card { position:relative; z-index:10; width:100%; max-width:420px; margin:24px; background:rgba(255,255,255,.06); backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px); border:1px solid rgba(255,255,255,.12); border-radius:28px; padding:40px; box-shadow:0 32px 64px rgba(0,0,0,.4); }
.auth-brand { text-align:center; margin-bottom:26px; }
.auth-logo { display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:18px; background:linear-gradient(135deg,#1D6FE8 0%,#0EA5E9 100%); box-shadow:0 10px 28px rgba(29,111,232,.45); font-size:28px; color:#fff; margin-bottom:16px; }
.auth-title { font-size:22px; font-weight:800; color:#fff; margin:0 0 6px; letter-spacing:-.3px; }
.auth-sub { font-size:13px; color:rgba(255,255,255,.5); margin:0; line-height:1.5; }
.auth-label { font-size:13px; color:rgba(255,255,255,.7); font-weight:600; margin-bottom:6px; display:block; }
.auth-input { width:100%; background:rgba(255,255,255,.07); border:1.5px solid rgba(255,255,255,.1); border-radius:14px; padding:13px 16px; color:#fff; font-size:14px; outline:none; transition:border-color .2s, box-shadow .2s; }
.auth-input::placeholder { color:rgba(255,255,255,.3); }
.auth-input:focus { border-color:rgba(29,111,232,.7); background:rgba(255,255,255,.1); box-shadow:0 0 0 4px rgba(29,111,232,.18); }
.auth-btn { margin-top:10px; width:100%; background:linear-gradient(135deg,#1D6FE8 0%,#0EA5E9 100%); border:none; border-radius:14px; padding:13px 20px; color:#fff; font-size:15px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 10px 28px rgba(29,111,232,.4); transition:transform .2s, filter .2s, opacity .2s; }
.auth-btn:hover { transform:translateY(-2px); filter:brightness(1.08); }
.auth-btn:disabled { cursor:wait; opacity:.78; transform:none; filter:none; }
.auth-alert { border-radius:12px; padding:10px 14px; font-size:13px; margin-bottom:18px; display:flex; align-items:center; gap:8px; background:rgba(16,185,129,.16); border:1px solid rgba(52,211,153,.42); color:#A7F3D0; font-weight:700; }
.auth-alert i { color:#34D399; }
.auth-alert.err { background:rgba(220,38,38,.15); border:1px solid rgba(239,68,68,.3); color:#FCA5A5; }
.auth-alert.err i { color:#FCA5A5; }
.auth-link { display:block; text-align:center; margin-top:18px; color:rgba(255,255,255,.6); font-size:13px; text-decoration:none; }
.auth-link:hover { color:rgba(255,255,255,.9); }
</style>
<div class="auth-bg"></div>
