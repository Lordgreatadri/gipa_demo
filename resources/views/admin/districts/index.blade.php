<x-admin-layout title="District management">
	<div class="admin-page-heading">
		<div><p class="admin-kicker">National registry</p><h1>{{ $showOverview ? 'District overview' : 'District list' }}</h1><p>{{ $showOverview ? 'Compare publication, population and readiness performance nationally.' : 'Govern individual district profiles, assignments and publication status.' }}</p></div>
		@can('districts.submit')<a class="button button--gold" href="{{ route('staff.districts.create') }}">Add district</a>@endcan
	</div>
	@if($showOverview)
	<section class="metric-widget-grid" aria-label="District overview">
		<x-metric-widget label="National registry" :value="number_format($metrics['total'])" :note="number_format($metrics['published']).' public profiles'" icon="landmark" tone="green" />
		<x-metric-widget label="Population mapped" :value="number_format($metrics['population']/1000000, 2).'M'" :note="number_format($metrics['population']).' residents recorded'" icon="users-round" tone="blue" />
		<x-metric-widget label="Average readiness" :value="$metrics['readiness'] !== null ? number_format($metrics['readiness'], 1).'%' : '—'" :note="number_format($metrics['under_review']).' profiles under review'" icon="gauge" tone="gold" />
		<x-metric-widget label="Infrastructure score" :value="$metrics['infrastructure'] !== null ? number_format($metrics['infrastructure'], 1).'%' : '—'" note="Average quality indicator" icon="route" tone="red" />
	</section>
	<section class="dashboard-chart-grid" aria-label="District analytics">
		<x-chart-panel title="Registry publication" description="Governance state across all district profiles." type="doughnut" :labels="$charts['status']['labels']" :datasets="[['label'=>'Districts','data'=>$charts['status']['values']]]" :summary="'District publication figures: '.collect($charts['status']['labels'])->zip($charts['status']['values'])->map(fn($item) => $item[0].' '.$item[1])->join(', ')" />
		<x-chart-panel title="Investment readiness bands" description="District concentration across strategic readiness thresholds." type="bar" :labels="$charts['readiness']['labels']" :datasets="[['label'=>'Districts','data'=>$charts['readiness']['values']]]" :summary="'District readiness bands: '.collect($charts['readiness']['labels'])->zip($charts['readiness']['values'])->map(fn($item) => $item[0].' '.$item[1])->join(', ')" />
		<x-chart-panel title="Regional readiness and reach" description="Average readiness compared with recorded population coverage." type="bar" :labels="$charts['regions']['labels']" :datasets="[['label'=>'Average readiness %','data'=>$charts['regions']['readiness']],['label'=>'Population millions','data'=>$charts['regions']['population'],'yAxisID'=>'y1']]" :summary="'Regional readiness percentages: '.collect($charts['regions']['labels'])->zip($charts['regions']['readiness'])->map(fn($item) => $item[0].' '.$item[1])->join(', ')" wide />
	</section>
	@endif
	@if($showList)
	<form class="admin-filterbar" method="get"><label><span>Status</span><select name="status" data-auto-submit><option value="">All statuses</option>@foreach(['draft'=>'Draft','under_review'=>'Under review','published'=>'Published'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label><a href="{{ route('staff.districts.index') }}">Reset</a></form>
	<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>District</th><th>Region</th><th>Status</th><th>Reviewer</th><th>SLA</th><th>Actions</th></tr></thead><tbody>
		@forelse($districts as $district)<tr><td><strong>{{ $district->name }}</strong><small>{{ $district->code }}</small></td><td>{{ $district->region->name }}</td><td><span class="admin-status admin-status--{{ $district->workflow_status }}">{{ str($district->workflow_status)->replace('_',' ')->title() }}</span></td><td>{{ $district->reviewer?->name ?? 'Unassigned' }}</td><td @class(['is-overdue'=>$district->sla_due_at?->isPast()])>{{ $district->sla_due_at?->diffForHumans() ?? '—' }}</td><td><div class="table-actions"><a href="{{ route('staff.districts.show',$district) }}">Open</a>@if($district->workflow_status === 'draft' && auth()->user()->can('districts.submit'))<a href="{{ route('staff.districts.edit',$district) }}">Edit</a><form method="post" action="{{ route('staff.districts.destroy',$district) }}" data-confirm="Delete this district draft?">@csrf @method('DELETE')<button type="submit">Delete</button></form>@endif</div></td></tr>
		@empty<tr><td colspan="6" class="table-empty">No districts match this view.</td></tr>@endforelse
	</tbody></table></div><div class="admin-pagination">{{ $districts->links() }}</div>
	@endif
</x-admin-layout>