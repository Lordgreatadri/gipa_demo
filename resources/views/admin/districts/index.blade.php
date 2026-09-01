<x-admin-layout title="District management">
	<div class="admin-page-heading">
		<div><p class="admin-kicker">National registry</p><h1>Districts</h1><p>Govern publication of all district profiles and readiness records.</p></div>
		@can('districts.submit')<a class="button button--gold" href="{{ route('staff.districts.create') }}">Add district</a>@endcan
	</div>
	<section class="metric-grid" aria-label="District overview">
		@foreach([
			['label'=>'Districts','value'=>number_format($metrics['total']),'note'=>'Registry total','tone'=>'green'],
			['label'=>'Published','value'=>number_format($metrics['published']),'note'=>'Public profiles','tone'=>'blue'],
			['label'=>'Under review','value'=>number_format($metrics['under_review']),'note'=>'Awaiting decision','tone'=>'gold'],
			['label'=>'Population covered','value'=>number_format($metrics['population']),'note'=>'Recorded residents','tone'=>'green'],
			['label'=>'Avg. readiness','value'=>$metrics['readiness'] !== null ? number_format($metrics['readiness'], 1).'%' : '—','note'=>'Across scored districts','tone'=>'gold'],
			['label'=>'Infrastructure','value'=>$metrics['infrastructure'] !== null ? number_format($metrics['infrastructure'], 1).'%' : '—','note'=>'Average quality score','tone'=>'blue'],
		] as $metric)
			<article class="metric metric--{{ $metric['tone'] }}"><span>{{ $metric['label'] }}</span><strong>{{ $metric['value'] }}</strong><small>{{ $metric['note'] }}</small></article>
		@endforeach
	</section>
	<form class="admin-filterbar" method="get"><label><span>Status</span><select name="status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach(['draft'=>'Draft','under_review'=>'Under review','published'=>'Published'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label><a href="{{ route('staff.districts.index') }}">Reset</a></form>
	<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>District</th><th>Region</th><th>Status</th><th>Reviewer</th><th>SLA</th><th>Actions</th></tr></thead><tbody>
		@forelse($districts as $district)<tr><td><strong>{{ $district->name }}</strong><small>{{ $district->code }}</small></td><td>{{ $district->region->name }}</td><td><span class="admin-status admin-status--{{ $district->workflow_status }}">{{ str($district->workflow_status)->replace('_',' ')->title() }}</span></td><td>{{ $district->reviewer?->name ?? 'Unassigned' }}</td><td @class(['is-overdue'=>$district->sla_due_at?->isPast()])>{{ $district->sla_due_at?->diffForHumans() ?? '—' }}</td><td><div class="table-actions"><a href="{{ route('staff.districts.show',$district) }}">Open</a>@if($district->workflow_status === 'draft' && auth()->user()->can('districts.submit'))<a href="{{ route('staff.districts.edit',$district) }}">Edit</a><form method="post" action="{{ route('staff.districts.destroy',$district) }}" onsubmit="return confirm('Delete this district draft?')">@csrf @method('DELETE')<button type="submit">Delete</button></form>@endif</div></td></tr>
		@empty<tr><td colspan="6" class="table-empty">No districts match this view.</td></tr>@endforelse
	</tbody></table></div><div class="admin-pagination">{{ $districts->links() }}</div>
</x-admin-layout>