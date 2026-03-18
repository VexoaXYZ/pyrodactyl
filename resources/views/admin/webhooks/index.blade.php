@extends('layouts.admin')

@section('title')
    Webhooks
@endsection

@section('content-header')
    <h1>Webhooks<small>Configure webhook endpoints for server events.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Webhooks</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Webhook Configurations</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.webhooks.new') }}" class="btn btn-sm btn-primary">Create New</a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Description</th>
                            <th>Endpoint</th>
                            <th>Type</th>
                            <th>Events</th>
                            <th class="text-center">Enabled</th>
                            <th class="text-center">Executions</th>
                        </tr>
                        @foreach ($webhooks as $webhook)
                            <tr>
                                <td><code>{{ $webhook->id }}</code></td>
                                <td>
                                    <a href="{{ route('admin.webhooks.view', $webhook->id) }}">
                                        {{ $webhook->description ?? 'No Description' }}
                                    </a>
                                </td>
                                <td><code>{{ Str::limit($webhook->endpoint, 50) }}</code></td>
                                <td>
                                    @if($webhook->type === 'discord')
                                        <span class="label label-info">Discord</span>
                                    @else
                                        <span class="label label-default">Regular</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($webhook->events as $event)
                                        <span class="label label-primary">{{ $event }}</span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @if($webhook->is_enabled)
                                        <span class="label label-success">Yes</span>
                                    @else
                                        <span class="label label-danger">No</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $webhook->webhooks_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
