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
        <thead><tr><th>Time</th><th>Category</th><th>Text</th><th>Screenshot</th></tr></thead>
        <tbody>
        @foreach($feedback as $entry)
          <tr>
            <td class="muted">{{ $entry['time'] ?? '' }}</td>
            <td><span class="tag">{{ $entry['category'] ?? 'other' }}</span></td>
            <td>{{ $entry['text'] ?? '' }}</td>
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
    <h2>Activity Log <span class="count">(showing {{ count($log) }} of {{ $totalLogEntries }})</span></h2>
    @if(count($log) === 0)
      <div class="empty">No activity yet.</div>
    @else
      <table>
        <thead><tr><th>Time</th><th>Event</th><th>Session</th><th>IP</th><th>Referrer</th></tr></thead>
        <tbody>
        @foreach($log as $entry)
          <tr>
            <td class="muted">{{ $entry['time'] ?? '' }}</td>
            <td><span class="tag">{{ $entry['type'] ?? '' }}</span></td>
            <td class="muted">{{ substr($entry['session'] ?? '', 0, 8) }}</td>
            <td class="muted">{{ $entry['ip'] ?? '' }}</td>
            <td class="muted">{{ $entry['referrer'] ?? '' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </section>
</main>

</body>
</html>
