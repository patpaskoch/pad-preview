<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin &mdash; Pad Preview</title>
<style>
  :root{ --bg:#0e1013; --panel:#16191d; --panel-2:#1d2126; --line:#2a2f36; --text:#e8e6e0; --muted:#8b9099; --accent:#c98a3d; }
  *{box-sizing:border-box;}
  body{ margin:0; background:var(--bg); color:var(--text); font-family:'Segoe UI', system-ui, sans-serif; }
  header{ padding:18px 24px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; }
  header h1{ margin:0; font-size:1.15rem; }
  header form button{
    background:var(--panel-2); border:1px solid var(--line); color:var(--text);
    padding:7px 14px; border-radius:8px; font-size:.8rem; cursor:pointer;
  }
  main{ max-width:1100px; margin:0 auto; padding:24px; }
  section{ margin-bottom:36px; }
  h2{ font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin:0 0 12px; }
  table{ width:100%; border-collapse:collapse; font-size:.82rem; }
  th, td{ text-align:left; padding:8px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
  th{ color:var(--muted); font-weight:600; font-size:.72rem; text-transform:uppercase; }
  tr:hover td{ background:var(--panel-2); }
  .tag{ display:inline-block; padding:2px 8px; border-radius:6px; background:var(--panel-2); border:1px solid var(--line); font-size:.72rem; }
  .muted{ color:var(--muted); }
  .empty{ color:var(--muted); font-size:.85rem; padding:16px 0; }
  a{ color:var(--accent); }
  .count{ color:var(--muted); font-size:.78rem; font-weight:400; text-transform:none; letter-spacing:0; }

  .visitor-list{ display:flex; flex-direction:column; gap:8px; }
  .visitor-card{
    background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden;
  }
  .visitor-card summary{
    list-style:none; cursor:pointer; padding:12px 14px;
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    font-size:.82rem;
  }
  .visitor-card summary::-webkit-details-marker{ display:none; }
  .visitor-card summary::before{
    content:'▸'; color:var(--muted); font-size:.7rem; width:10px; flex-shrink:0;
    transition:transform .15s;
  }
  .visitor-card[open] summary::before{ transform:rotate(90deg); }
  .visitor-card summary:hover{ background:var(--panel-2); }
  .v-id{ font-weight:600; }
  .v-fill{ flex:1; }
  .visitor-card .visitor-events{ border-top:1px solid var(--line); }
  .visitor-card .visitor-events table{ margin:0; }
  .visitor-card .visitor-events th, .visitor-card .visitor-events td{ padding:6px 14px; }

  .summary-grid{ display:flex; flex-wrap:wrap; gap:10px; }
  .stat{
    background:var(--panel); border:1px solid var(--line); border-radius:10px;
    padding:14px 18px; min-width:110px;
  }
  .stat-num{ display:block; font-size:1.4rem; font-weight:700; }
  .stat-label{ display:block; color:var(--muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; margin-top:2px; }
</style>
</head>
<body>

<header>
  <h1>Pad Preview &mdash; Admin</h1>
  <form method="POST" action="{{ route('admin.logout') }}">
    @csrf
    <button type="submit">Log out</button>
  </form>
</header>

<main>
  <section>
    <h2>Feedback ({{ count($feedback) }})</h2>
    @if(count($feedback) === 0)
      <div class="empty">No feedback yet.</div>
    @else
      <table>
        <thead><tr><th>Time</th><th>Category</th><th>Text</th><th>Email</th><th>Screenshot</th></tr></thead>
        <tbody>
        @foreach($feedback as $entry)
          <tr>
            <td class="muted">{{ friendly_date($entry['time'] ?? null) }}</td>
            <td><span class="tag">{{ $entry['category'] ?? 'other' }}</span></td>
            <td>{{ $entry['text'] ?? '' }}</td>
            <td>
              @if(!empty($entry['email']))
                <a href="mailto:{{ $entry['email'] }}">{{ $entry['email'] }}</a>
              @else
                <span class="muted">&mdash;</span>
              @endif
            </td>
            <td>
              @if(!empty($entry['screenshot']))
                <a href="{{ route('admin.screenshot', $entry['screenshot']) }}" target="_blank">view</a>
              @else
                <span class="muted">&mdash;</span>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </section>

  <section>
    <h2>Visitors <span class="count">({{ $totalVisitors }} unique, {{ $totalEvents }} events total)</span></h2>
    @if(count($visitors) === 0)
      <div class="empty">No visitors yet.</div>
    @else
      <div class="visitor-list">
        @foreach($visitors as $v)
          <details class="visitor-card">
            <summary>
              <span class="v-id">{{ substr($v['session'], 0, 8) }}</span>
              <span class="muted">{{ $v['ip'] ?? '—' }}</span>
              <span class="tag">{{ $v['count'] }} event{{ $v['count'] == 1 ? '' : 's' }}</span>
              <span class="v-fill"></span>
              <span class="muted">first: {{ friendly_date($v['first_seen']) }}</span>
              <span class="muted">last: {{ friendly_date($v['last_seen']) }}</span>
            </summary>
            <div class="visitor-events">
              <table>
                <thead><tr><th>Time</th><th>Event</th><th>Details</th></tr></thead>
                <tbody>
                @foreach($v['events'] as $e)
                  <tr>
                    <td class="muted">{{ friendly_date($e['time'] ?? null) }}</td>
                    <td><span class="tag">{{ $e['type'] ?? '' }}</span></td>
                    <td class="muted">
                      {{ !empty($e['meta']) ? json_encode($e['meta']) : '' }}
                      @if(!empty($e['referrer'])) via {{ $e['referrer'] }} @endif
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </details>
        @endforeach
      </div>
    @endif
  </section>

  <section>
    <h2>Summary</h2>
    <div class="summary-grid">
      <div class="stat">
        <span class="stat-num">{{ $totalVisitors }}</span>
        <span class="stat-label">Visitors</span>
      </div>
      <div class="stat">
        <span class="stat-num">{{ $totalEvents }}</span>
        <span class="stat-label">Total events</span>
      </div>
      @foreach($eventCounts as $type => $count)
        <div class="stat">
          <span class="stat-num">{{ $count }}</span>
          <span class="stat-label">{{ $type }}</span>
        </div>
      @endforeach
    </div>
  </section>
</main>

</body>
</html>
