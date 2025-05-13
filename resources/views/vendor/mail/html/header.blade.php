@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<span style="font-weight: bold; font-size: 24px; color: #333;">PMS</span>
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
