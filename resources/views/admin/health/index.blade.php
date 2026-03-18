@extends('layouts.admin')

@section('title')
    Health
@endsection

@section('content-header')
    <h1>System Health<small>Monitor the health of your panel infrastructure.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Health</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Health Checks</h3>
                <div class="box-tools">
                    @if($lastRanAt)
                        <span class="label label-default">Last checked: {{ $lastRanAt->diffForHumans() }}</span>
                    @endif
                    <a href="{{ route('admin.health') }}" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                        <i class="fa fa-refresh"></i> Refresh
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th style="width: 50px;" class="text-center">Status</th>
                            <th>Check</th>
                            <th>Result</th>
                            <th>Details</th>
                        </tr>
                        @forelse($checkResults as $result)
                            <tr>
                                <td class="text-center">
                                    @if($result->status === 'ok')
                                        <span class="label label-success"><i class="fa fa-check"></i></span>
                                    @elseif($result->status === 'warning')
                                        <span class="label label-warning"><i class="fa fa-exclamation-triangle"></i></span>
                                    @else
                                        <span class="label label-danger"><i class="fa fa-times"></i></span>
                                    @endif
                                </td>
                                <td><strong>{{ $result->label }}</strong></td>
                                <td>
                                    @if($result->status === 'ok')
                                        <span class="text-success">Healthy</span>
                                    @elseif($result->status === 'warning')
                                        <span class="text-warning">Warning</span>
                                    @else
                                        <span class="text-danger">Failed</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $result->shortSummary }}
                                    @if($result->notificationMessage)
                                        <br><small class="text-muted">{{ $result->notificationMessage }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No health checks have been run yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">API Endpoint</h3>
            </div>
            <div class="box-body">
                <p>A JSON health endpoint is available at:</p>
                <pre>GET /api/health</pre>
                <p class="text-muted small">Returns HTTP 200 when all checks pass, HTTP 503 when any check fails. No authentication required — suitable for uptime monitoring services.</p>
            </div>
        </div>
    </div>
</div>
@endsection
