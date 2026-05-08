<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroRD — Agro dominicano: productores, productos y mercados | Vive RD</title>
    <meta name="description" content="Agricultura y campo dominicano: productores, cosechas, mercados y agro-tecnología. Parte del paraguas digital Vive RD.">
    <link rel="canonical" href="https://agro.vrd.do/">
    <meta property="og:title" content="AgroRD — Agro dominicano">
    <meta property="og:description" content="Productores, cosechas y mercados de la República Dominicana">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://agro.vrd.do/">
    <style>
        :root {
            --pri: #16a34a;
            --sec: #14532d;
            --bg-soft: #f0fdf4;
            --text: #1f2937;
            --text-soft: #4b5563;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-soft);
            color: var(--text);
            line-height: 1.6;
        }
        .paraguas-bar {
            background: linear-gradient(90deg, #002D62 0%, #002D62 33.3%, white 33.3%, white 66.6%, #CE1126 66.6%, #CE1126 100%);
            height: 4px;
        }
        .paraguas-link {
            background: var(--sec);
            color: white;
            text-align: center;
            padding: 8px 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .paraguas-link a { color: white; text-decoration: underline; }
        header {
            background: linear-gradient(135deg, var(--pri) 0%, var(--sec) 100%);
            color: white;
            padding: 80px 24px 100px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 900;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
        }
        .emoji-hero { font-size: 48px; display: block; margin-bottom: 12px; }
        .tagline {
            font-size: clamp(18px, 2.5vw, 24px);
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
        }
        section {
            max-width: 1100px;
            margin: 0 auto;
            padding: 60px 24px;
        }
        h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--sec);
            margin-bottom: 24px;
            text-align: center;
        }
        .cats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .cat {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.15s, border-color 0.15s;
        }
        .cat:hover { transform: translateY(-2px); border-color: var(--pri); }
        .cat-emoji { font-size: 32px; display: block; margin-bottom: 8px; }
        .cat-name { font-weight: 700; color: var(--sec); margin-bottom: 4px; }
        .cat-desc { font-size: 13px; color: var(--text-soft); }
        .cta {
            background: white;
            border-radius: 16px;
            padding: 40px;
            border: 2px solid var(--pri);
            text-align: center;
            box-shadow: 0 4px 12px rgba(22,163,74,0.08);
        }
        .cta h3 {
            font-size: 24px;
            font-weight: 800;
            color: var(--sec);
            margin-bottom: 12px;
        }
        .cta p { color: var(--text-soft); margin-bottom: 24px; }
        .btn {
            display: inline-block;
            background: var(--pri);
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: background 0.15s;
        }
        .btn:hover { background: var(--sec); }
        footer {
            background: var(--sec);
            color: white;
            padding: 40px 24px;
            text-align: center;
            font-size: 14px;
        }
        footer a { color: white; }
        .verticales-paraguas {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 16px;
            font-size: 12px;
        }
        .vertical-pill {
            background: rgba(255,255,255,0.1);
            padding: 4px 12px;
            border-radius: 999px;
        }
    </style>
</head>
<body>
    <div class="paraguas-bar"></div>
    <div class="paraguas-link">
        🇩🇴 Parte del paraguas <a href="https://vrd.do/">Vive RD →</a>
    </div>

    <header>
        <span class="badge">PILAR TURISMO · MAYO 2026</span>
        <span class="emoji-hero">🌱</span>
        <h1>AgroRD</h1>
        <p class="tagline">Agricultura y campo dominicano: productores, cosechas, mercados y agro-tecnología</p>
    </header>

    <section>
        <h2>Categorías del agro dominicano</h2>
        <div class="cats">
            <div class="cat"><span class="cat-emoji">🥭</span><div class="cat-name">Frutas tropicales</div><div class="cat-desc">Mango, piña, papaya, guineo</div></div>
            <div class="cat"><span class="cat-emoji">🥬</span><div class="cat-name">Vegetales</div><div class="cat-desc">Hortalizas frescas RD</div></div>
            <div class="cat"><span class="cat-emoji">🌾</span><div class="cat-name">Granos</div><div class="cat-desc">Arroz, habichuela, maíz</div></div>
            <div class="cat"><span class="cat-emoji">☕</span><div class="cat-name">Café y cacao</div><div class="cat-desc">Cibao y Barahona</div></div>
            <div class="cat"><span class="cat-emoji">🚜</span><div class="cat-name">Maquinaria</div><div class="cat-desc">Equipos agrícolas</div></div>
            <div class="cat"><span class="cat-emoji">🐄</span><div class="cat-name">Ganadería</div><div class="cat-desc">Carne y lácteos</div></div>
            <div class="cat"><span class="cat-emoji">🍯</span><div class="cat-name">Apicultura</div><div class="cat-desc">Miel y derivados</div></div>
            <div class="cat"><span class="cat-emoji">🐔</span><div class="cat-name">Avicultura</div><div class="cat-desc">Aves y huevos</div></div>
            <div class="cat"><span class="cat-emoji">🐟</span><div class="cat-name">Acuicultura</div><div class="cat-desc">Tilapia, camarones</div></div>
            <div class="cat"><span class="cat-emoji">🌿</span><div class="cat-name">Orgánicos</div><div class="cat-desc">Certificados RD</div></div>
            <div class="cat"><span class="cat-emoji">🌱</span><div class="cat-name">Insumos</div><div class="cat-desc">Semillas, fertilizantes</div></div>
            <div class="cat"><span class="cat-emoji">🚛</span><div class="cat-name">Logística</div><div class="cat-desc">Distribución mayorista</div></div>
        </div>
    </section>

    <section>
        <div class="cta">
            <h3>Próximamente: marketplace agro dominicano</h3>
            <p>Conectamos productores, distribuidores, mercados y consumidores en una sola plataforma del agro RD.</p>
            <a href="mailto:info@agro.vrd.do" class="btn">Contactar →</a>
        </div>
    </section>

    <footer>
        <p><strong>AgroRD</strong> · {{ date('Y') }} · Parte del paraguas <a href="https://vrd.do/">Vive RD</a></p>
        <div class="verticales-paraguas">
            <span class="vertical-pill"><a href="https://estilo.vrd.do/">✨ EstiloRD</a></span>
            <span class="vertical-pill"><a href="https://servi.vrd.do/">🛠️ ServiRD</a></span>
            <span class="vertical-pill"><a href="https://inmo.vrd.do/">🏠 InmoRD</a></span>
            <span class="vertical-pill"><a href="https://educ.vrd.do/">📚 EducRD</a></span>
            <span class="vertical-pill"><a href="https://turismo.vrd.do/">🌴 Visit RD</a></span>
        </div>
    </footer>
</body>
</html>
