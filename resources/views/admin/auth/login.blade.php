<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion Admin — TholadImmo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  background:#FFFFFF;
  position:relative;
  overflow:hidden;
}

/* Décorations de fond subtiles couleurs Tholad */
body::before{
  content:'';position:absolute;top:-180px;right:-180px;
  width:520px;height:520px;
  background:radial-gradient(circle, rgba(0,71,255,.06) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;
}
body::after{
  content:'';position:absolute;bottom:-130px;left:-130px;
  width:380px;height:380px;
  background:radial-gradient(circle, rgba(139,0,0,.05) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;
}

/* Barre Tholad en haut */
.tholad-topbar{
  position:fixed;top:0;left:0;right:0;
  height:3px;
  background:linear-gradient(90deg, #0047FF 50%, #1A0099 72%, #8B0000 100%);
  z-index:100;
}

.wrapper{
  width:100%;max-width:460px;
  padding:20px;
  position:relative;z-index:1;
  flex:1;display:flex;align-items:center;justify-content:center;
}

/* Card */
.card{
  width:100%;
  background:#fff;
  border-radius:24px;
  padding:48px 44px;
  box-shadow:0 20px 60px rgba(0,40,150,.10), 0 2px 12px rgba(0,0,0,.05);
  border:1px solid rgba(0,71,255,.08);
}

/* Logo */
.logo-wrap{
  text-align:center;
  margin-bottom:28px;
}
.logo-wrap img{
  height:90px;width:auto;
  margin-bottom:12px;
  filter:drop-shadow(0 4px 12px rgba(0,71,255,.12));
}
.logo-wrap h1{
  font-family:'Cormorant Garamond',serif;
  font-size:26px;font-weight:700;
  color:#0F1F3D;margin-bottom:4px;
}
.logo-wrap p{
  font-size:12.5px;color:#6B7280;letter-spacing:.3px;
}

/* Ligne couleurs Tholad */
.brand-line{
  display:flex;height:3px;
  border-radius:4px;overflow:hidden;
  margin-bottom:30px;
}
.brand-line span:nth-child(1){flex:2;background:#0047FF;}
.brand-line span:nth-child(2){flex:1;background:#1A0099;}
.brand-line span:nth-child(3){flex:1;background:#8B0000;}

/* Alert */
.alert{
  padding:12px 16px;border-radius:12px;
  font-size:13px;margin-bottom:22px;
  display:flex;align-items:center;gap:10px;
  background:#FEF2F2;color:#991B1B;
  border:1px solid #FECACA;
  animation:shake .4s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}

/* Form */
.form-group{margin-bottom:16px;}
.form-group label{
  display:block;font-size:11.5px;font-weight:700;
  color:#374151;margin-bottom:7px;
  text-transform:uppercase;letter-spacing:.6px;
}
.input-wrap{position:relative;}
.input-icon{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:#9CA3AF;font-size:15px;pointer-events:none;
}
.form-control{
  width:100%;padding:13px 14px 13px 42px;
  border:1.5px solid #E5E7EB;border-radius:12px;
  font-size:14px;font-family:'DM Sans',sans-serif;
  background:#FAFAFA;color:#111827;
  outline:none;transition:all .2s;
}
.form-control:focus{
  border-color:#0047FF;background:#fff;
  box-shadow:0 0 0 4px rgba(0,71,255,.08);
}
.form-control::placeholder{color:#9CA3AF;}

/* Remember */
.remember{
  display:flex;align-items:center;gap:9px;
  margin-bottom:24px;cursor:pointer;
}
.remember input{width:16px;height:16px;cursor:pointer;accent-color:#0047FF;}
.remember span{font-size:13px;color:#4B5563;}

/* Bouton */
.btn-login{
  width:100%;padding:15px;
  background:linear-gradient(135deg, #0047FF, #1A0099);
  color:#fff;border:none;border-radius:12px;
  font-size:15px;font-weight:700;
  font-family:'DM Sans',sans-serif;
  cursor:pointer;transition:all .25s;
  letter-spacing:.3px;
  box-shadow:0 6px 20px rgba(0,71,255,.3);
  position:relative;overflow:hidden;
}
.btn-login::before{
  content:'';position:absolute;top:0;left:-100%;
  width:100%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.12),transparent);
  transition:.5s;
}
.btn-login:hover::before{left:100%;}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(0,71,255,.4);}
.btn-login:active{transform:translateY(0);}

/* Badge sécurité */
.security-badge{
  display:flex;align-items:center;justify-content:center;
  gap:6px;margin-top:18px;
  font-size:11.5px;color:#9CA3AF;
}

/* Footer */
footer{
  width:100%;
  padding:20px;
  text-align:center;
  border-top:1px solid #F0F0F0;
  background:#fff;
  position:relative;z-index:1;
}
footer .footer-logo{
  display:flex;align-items:center;justify-content:center;
  gap:8px;margin-bottom:6px;
}
footer .footer-logo img{height:18px;width:auto;opacity:.7;}
footer .footer-logo span{
  font-family:'Cormorant Garamond',serif;
  font-size:14px;font-weight:700;color:#0F1F3D;
}
footer p{font-size:12px;color:#9CA3AF;}
footer strong{color:#4B5563;font-weight:700;}
</style>
</head>
<body>

<div class="tholad-topbar"></div>

<div class="wrapper">
  <div class="card">

    <!-- Logo Tholad -->
    <div class="logo-wrap">
      <img src="{{ asset('images/tholad-logo.png') }}"
           onerror="this.style.display='none';document.getElementById('logo-fallback').style.display='block'"
           alt="Tholad Group">
      <div id="logo-fallback" style="display:none;font-size:52px;margin-bottom:10px">🏠</div>
      <h1>TholadImmo</h1>
      <p>Espace Administrateur — Tholad Group</p>
    </div>

    <!-- Ligne couleurs Tholad -->
    <div class="brand-line">
      <span></span><span></span><span></span>
    </div>

    <!-- Erreurs -->
    @if($errors->any())
    <div class="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ $errors->first() }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ session('error') }}
    </div>
    @endif

    <!-- Formulaire -->
    <form action="{{ route('admin.login.post') }}" method="POST">
      @csrf

      <div class="form-group">
        <label>Adresse email</label>
        <div class="input-wrap">
          <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <input type="email" name="email" class="form-control"
                 placeholder="admin@immostay.com"
                 value="{{ old('email') }}" required autocomplete="email" autofocus>
        </div>
      </div>

      <div class="form-group">
        <label>Mot de passe</label>
        <div class="input-wrap">
          <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" name="password" class="form-control"
                 placeholder="••••••••" required autocomplete="current-password">
        </div>
      </div>

      <label class="remember">
        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
        <span>Rester connecté</span>
      </label>

      <button type="submit" class="btn-login">
        Se connecter →
      </button>
    </form>

    <div class="security-badge">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Connexion sécurisée SSL
    </div>

  </div>
</div>

<!-- Footer -->
<footer>
  <div class="footer-logo">
    <img src="{{ asset('images/tholad-logo.png') }}"
         onerror="this.style.display='none'"
         alt="Tholad Group">
    <span>ImmoStay</span>
  </div>
  <p>
    © {{ date('Y') }} <strong>TholadImmo</strong> — Tholad Group.
    Développé avec ❤️ par <strong>Basile NGASSAKI ZONI</strong>
  </p>
</footer>

</body>
</html>
