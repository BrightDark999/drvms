<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DRMS Public API</title>
    <style>
        :root { color-scheme: light dark; --accent: #2563eb; --panel: color-mix(in srgb, Canvas 94%, CanvasText 6%); }
        * { box-sizing: border-box; }
        body { margin: 0; font: 16px/1.55 system-ui, sans-serif; background: Canvas; color: CanvasText; }
        main { width: min(100% - 2rem, 960px); margin: 3rem auto; }
        h1 { margin-bottom: .25rem; } h2 { margin-top: 2rem; }
        .muted { opacity: .72; } .card { padding: 1.25rem; margin: 1rem 0; background: var(--panel); border: 1px solid color-mix(in srgb, CanvasText 15%, transparent); border-radius: .75rem; }
        .method { display: inline-block; padding: .15rem .5rem; margin-right: .5rem; border-radius: .35rem; color: white; background: #15803d; font-weight: 700; font-size: .8rem; }
        code, pre { font-family: ui-monospace, monospace; } code { overflow-wrap: anywhere; }
        pre { overflow: auto; padding: 1rem; border-radius: .5rem; background: color-mix(in srgb, CanvasText 8%, Canvas); }
        button, a.button { display: inline-block; border: 0; padding: .6rem .85rem; border-radius: .45rem; background: var(--accent); color: white; text-decoration: none; cursor: pointer; }
        #result { min-height: 5rem; max-height: 32rem; }
    </style>
</head>
<body>
<main>
    <h1>DRMS Public API</h1>
    <p class="muted">Browsable, read-only inventory endpoints. No API token is required.</p>

    <section class="card">
        <p><span class="method">GET</span><code><?= esc($listUrl) ?></code></p>
        <p>Returns active inventory items. Optional query: <code>?limit=100</code> (1–500).</p>
        <button type="button" id="try-list">Try it</button>
    </section>

    <section class="card">
        <p><span class="method">GET</span><code><?= esc($itemUrl) ?></code></p>
        <p>Returns one active item by numeric ID.</p>
    </section>

    <h2>Example integration</h2>
    <pre><code>fetch('<?= esc($listUrl) ?>')
  .then(response =&gt; response.json())
  .then(result =&gt; console.log(result.data));</code></pre>

    <h2>Live response</h2>
    <pre id="result">Select “Try it” to load the endpoint.</pre>
</main>
<script>
document.getElementById('try-list').addEventListener('click', async () => {
    const output = document.getElementById('result');
    output.textContent = 'Loading…';
    try {
        const response = await fetch(<?= json_encode($listUrl) ?>);
        output.textContent = JSON.stringify(await response.json(), null, 2);
    } catch (error) {
        output.textContent = String(error);
    }
});
</script>
</body>
</html>
