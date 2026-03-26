<?php
function getNextSaturday730() {
    $now = new DateTime();
    $target = clone $now;

    // Find this Saturday
    $dayOfWeek = (int)$now->format('w'); // 0=Sun, 6=Sat
    $daysUntilSat = (6 - $dayOfWeek + 7) % 7;

    if ($daysUntilSat === 0) {
        // Today is Saturday — check if 7:30 AM has passed
        $cutoff = clone $now;
        $cutoff->setTime(7, 30, 0);
        if ($now >= $cutoff) {
            $daysUntilSat = 7; // next Saturday
        }
    }

    $target->modify("+{$daysUntilSat} days");
    $target->setTime(7, 30, 0);
    return $target;
}

$target = getNextSaturday730();
$targetTimestamp = $target->getTimestamp() * 1000; // JS milliseconds
$targetLabel = $target->format('l, F j, Y \a\t g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contador para conocer a Howl</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0f0f1a;
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
    }

    .container { text-align: center; }

    h1 {
      font-size: 1.3rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: #a0a0c0;
      margin-bottom: 3rem;
    }

    .blocks {
      display: flex;
      gap: 2rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .block {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
    }

    .number {
      font-size: 5rem;
      font-weight: 700;
      line-height: 1;
      background: linear-gradient(135deg, #a78bfa, #60a5fa);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      min-width: 2ch;
      font-variant-numeric: tabular-nums;
    }

    .label {
      font-size: 0.75rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: #6060a0;
    }

    .separator {
      font-size: 4rem;
      font-weight: 300;
      color: #3a3a60;
      align-self: flex-start;
      margin-top: 0.15rem;
    }

    .done {
      font-size: 2rem;
      color: #a78bfa;
    }

    footer {
      margin-top: 3rem;
      font-size: 0.8rem;
      color: #404060;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Cuánto falta para que Kuro y Key conozcan a su perro adoptado Howl Magnus <strong>Lance</strong>lot Oden</h1>
    <div class="blocks" id="timer"></div>
    <footer>Target: <?= htmlspecialchars($targetLabel) ?></footer>
  </div>

  <script>
    const target = new Date(<?= $targetTimestamp ?>);

    function pad(n) { return String(Math.floor(n)).padStart(2, '0'); }

    function render() {
      const now = new Date();
      const diff = target - now;
      const el = document.getElementById('timer');

      if (diff <= 0) {
        el.innerHTML = '<p class="done">🎉 It\'s time!</p>';
        return;
      }

      const h = Math.floor(diff / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);

      el.innerHTML = `
        <div class="block">
          <div class="number">${pad(h)}</div>
          <div class="label">Horas</div>
        </div>
        <div class="separator">:</div>
        <div class="block">
          <div class="number">${pad(m)}</div>
          <div class="label">Minutos</div>
        </div>
        <div class="separator">:</div>
        <div class="block">
          <div class="number">${pad(s)}</div>
          <div class="label">Segundos</div>
        </div>
      `;
    }

    render();
    setInterval(render, 1000);
  </script>
</body>
</html>