<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ config('app.name') }} — {{ $brand_tagline }}</title>
  <meta name="description" content="{{ $brand_tagline }}. Plataforma del ecosistema Marca País VRD.">
  <meta name="theme-color" content="{{ $brand_primary }}">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <meta property="og:type" content="website">
  <meta property="og:title" content="{{ config('app.name') }} — {{ $brand_tagline }}">
  <meta property="og:description" content="{{ $brand_tagline }}. Plataforma del ecosistema Marca País VRD.">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    :root{--pri:{{ $brand_primary }};--sec:{{ $brand_secondary }}}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
      background:linear-gradient(135deg,var(--pri)0a,var(--sec)0a);color:#1f2937;min-height:100vh;line-height:1.6}
    .top-bar{background:linear-gradient(90deg,var(--pri),var(--sec));color:white;padding:12px 24px;text-align:center;font-size:13px}
    .top-bar a{color:white;text-decoration:underline}
    main{max-width:1200px;margin:0 auto;padding:60px 24px}
    .hero{text-align:center;margin-bottom:80px}
    .hero-emoji{font-size:80px;animation:float 3s ease-in-out infinite;margin-bottom:16px}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
    .badge-beta{display:inline-flex;align-items:center;gap:8px;background:white;border:2px solid var(--pri);
      color:var(--pri);padding:8px 18px;border-radius:999px;font-size:13px;font-weight:700;
      letter-spacing:0.5px;text-transform:uppercase;margin-bottom:24px;box-shadow:0 4px 12px var(--pri)25}
    .badge-beta::before{content:'';width:8px;height:8px;background:var(--pri);border-radius:50%;animation:pulse 2s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
    h1{font-size:clamp(48px,9vw,96px);font-weight:800;line-height:1;margin-bottom:16px;
      background:linear-gradient(135deg,var(--pri),var(--sec));-webkit-background-clip:text;
      background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:-0.03em}
    .tagline{font-size:clamp(18px,3vw,26px);color:#4b5563;max-width:680px;margin:0 auto 32px;font-weight:500}
    .cta-row{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:32px}
    .btn-primary{background:linear-gradient(135deg,var(--pri),var(--sec));color:white;padding:18px 40px;
      border-radius:14px;text-decoration:none;font-weight:700;font-size:17px;letter-spacing:0.3px;
      box-shadow:0 8px 24px var(--pri)40;transition:transform 0.2s}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 32px var(--pri)55}
    .btn-secondary{background:white;color:var(--pri);padding:18px 40px;border-radius:14px;
      text-decoration:none;font-weight:700;font-size:17px;border:2px solid var(--pri);transition:all 0.2s}
    .btn-secondary:hover{background:var(--pri);color:white}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;
      max-width:800px;margin:48px auto 0;padding:24px;background:white;border-radius:20px;box-shadow:0 8px 32px var(--pri)10}
    .stat{text-align:center}
    .stat-num{font-size:32px;font-weight:800;color:var(--pri);line-height:1}
    .stat-label{font-size:13px;color:#6b7280;margin-top:4px;text-transform:uppercase;letter-spacing:0.5px}
    h2{font-size:32px;text-align:center;margin-bottom:8px;color:#111827}
    .section-sub{text-align:center;color:#6b7280;margin-bottom:40px;font-size:16px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;margin-bottom:60px}
    .cat-card{background:white;padding:32px 24px;border-radius:16px;text-align:center;
      transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;cursor:pointer;border:2px solid transparent;text-decoration:none;color:inherit}
    .cat-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px var(--pri)25;border-color:var(--pri)}
    .cat-icon{font-size:40px;color:var(--pri);margin-bottom:12px}
    .cat-name{font-size:18px;font-weight:700;color:#111827;margin-bottom:6px}
    .cat-desc{font-size:13px;color:#6b7280;margin-bottom:8px}
    .cat-empty{font-size:11px;color:var(--pri);font-weight:600;text-transform:uppercase;letter-spacing:0.5px}
    .how-it-works{background:white;padding:48px 32px;border-radius:24px;margin-top:60px;box-shadow:0 8px 32px var(--pri)10}
    .steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin-top:32px}
    .step{text-align:center}
    .step-num{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;
      background:linear-gradient(135deg,var(--pri),var(--sec));color:white;border-radius:50%;
      font-size:20px;font-weight:800;margin-bottom:12px}
    .step h3{font-size:17px;color:#111827;margin-bottom:6px}
    .step p{font-size:14px;color:#6b7280}
    footer{padding:40px 24px;text-align:center;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;margin-top:80px}
    footer a{color:var(--pri);text-decoration:none;font-weight:600}
  </style>
  <script type="application/ld+json">{"@@context":"https://schema.org","@@type":"Organization","name":"{{ config('app.name') }}","url":"{{ url('/') }}","description":"{{ $brand_tagline }}","sameAs":["https://visitrepublicadominicana.com"]}</script>
</head>
<body>
  <div class="top-bar">
    Parte del ecosistema <strong>Marca País VRD</strong> ·
    <a href="https://visitrepublicadominicana.com">Visit RD</a>
  </div>
  <main>
    <div class="hero">
      <div class="hero-emoji">{{ $brand_emoji ?? '🌟' }}</div>
      <span class="badge-beta">Beta abierto · Mayo 2026</span>
      <h1>{{ config('app.name') }}</h1>
      <p class="tagline">{{ $brand_tagline }}</p>
      <div class="cta-row">
        <a href="#registrar" class="btn-primary"><i class="fas fa-plus-circle"></i>&nbsp; Registrar tu salón, barbería o spa</a>
        <a href="#categorias" class="btn-secondary">Explorar categorías</a>
      </div>
      <div class="stats">
        <div class="stat"><div class="stat-num">{{ $categorias->count() }}</div><div class="stat-label">categorías disponibles</div></div>
        <div class="stat"><div class="stat-num">0</div><div class="stat-label">negocios registrados</div></div>
        <div class="stat"><div class="stat-num">100%</div><div class="stat-label">dominicano</div></div>
      </div>
    </div>
    <h2 id="categorias">Explora por categoría</h2>
    <p class="section-sub">{{ $categorias->count() }} categorías esperando los primeros negocios</p>
    <div class="grid">
      @foreach($categorias as $cat)
        <a href="#" class="cat-card">
          <div class="cat-icon"><i class="{{ $cat->icono }}"></i></div>
          <div class="cat-name">{{ $cat->nombre }}</div>
          <div class="cat-desc">{{ $cat->descripcion }}</div>
          <div class="cat-empty">Sé el primero</div>
        </a>
      @endforeach
    </div>
    <div class="how-it-works" id="registrar">
      <h2>Cómo registrar tu negocio</h2>
      <p class="section-sub">Tres pasos simples para sumarte al primer directorio dominicano de tu nicho</p>
      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <h3>Crea tu perfil</h3>
          <p>Registra tu negocio con nombre, ubicación y servicios. Toma 5 minutos.</p>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <h3>Sube tus fotos</h3>
          <p>Muestra tu trabajo, tus instalaciones y lo que te hace único.</p>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <h3>Recibe clientes</h3>
          <p>Visibilidad en Google y dentro del ecosistema Marca País VRD.</p>
        </div>
      </div>
      <div style="text-align:center;margin-top:32px">
        <a href="mailto:info@estilo.vrd.do?subject=Quiero%20registrar%20mi%20negocio" class="btn-primary">
          <i class="fas fa-envelope"></i>&nbsp; Quiero ser uno de los primeros
        </a>
      </div>
    </div>
  </main>
  <footer>
    <p>&copy; 2026 {{ config('app.name') }} · {{ $brand_tagline }}</p>
    <p style="margin-top:8px">{{ request()->getHost() }} · Una iniciativa de
      <a href="https://visitrepublicadominicana.com">Visit República Dominicana</a></p>
  </footer>
<div style="position:fixed;bottom:16px;left:16px;z-index:9999;"><a href="https://vrd.do" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#002D62;color:white;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.5px;text-decoration:none;transition:opacity 0.2s;" onmouseover="this.style.opacity=0.85" onmouseout="this.style.opacity=1">🇩🇴 Parte de Vive RD →</a></div>
</body>
</html>
