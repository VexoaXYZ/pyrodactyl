@extends('layouts.admin')

@section('title')
    Webhook &rarr; {{ $webhook->description ?? $webhook->endpoint }}
@endsection

@section('content-header')
    <h1>Webhook Configuration<small>{{ $webhook->description ?? Str::limit($webhook->endpoint, 50) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.webhooks') }}">Webhooks</a></li>
        <li class="active">{{ $webhook->id }}</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.webhooks.view', $webhook->id) }}" method="POST">
    {!! csrf_field() !!}
    {!! method_field('PATCH') !!}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pType" class="form-label">Type</label>
                                <select name="type" id="pType" class="form-control">
                                    <option value="regular" @if(old('type', $webhook->type) === 'regular') selected @endif>Regular</option>
                                    <option value="discord" @if(old('type', $webhook->type) === 'discord') selected @endif>Discord</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pEnabled" class="form-label">Enabled</label>
                                <select name="is_enabled" id="pEnabled" class="form-control">
                                    <option value="1" @if(old('is_enabled', $webhook->is_enabled ? '1' : '0') == '1') selected @endif>Yes</option>
                                    <option value="0" @if(old('is_enabled', $webhook->is_enabled ? '1' : '0') == '0') selected @endif>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pEndpoint" class="form-label">Endpoint URL</label>
                        <input type="url" name="endpoint" id="pEndpoint" class="form-control" value="{{ old('endpoint', $webhook->endpoint) }}" />
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">Description</label>
                        <input type="text" name="description" id="pDescription" class="form-control" value="{{ old('description', $webhook->description) }}" />
                    </div>
                    <div class="form-group">
                        <label for="pEvents" class="form-label">Events</label>
                        @php $currentEvents = old('events', $webhook->events ?? []); @endphp
                        <select name="events[]" id="pEvents" class="form-control" multiple>
                            <option value="server:created" @if(in_array('server:created', $currentEvents)) selected @endif>server:created</option>
                            <option value="server:updated" @if(in_array('server:updated', $currentEvents)) selected @endif>server:updated</option>
                            <option value="server:deleted" @if(in_array('server:deleted', $currentEvents)) selected @endif>server:deleted</option>
                        </select>
                        <p class="text-muted small">Select which events should trigger this webhook.</p>
                    </div>
                    <div id="headersSection">
                        <div class="form-group">
                            <label for="pHeaders" class="form-label">Custom Headers (JSON)</label>
                            <textarea name="headers" id="pHeaders" class="form-control" rows="3">{{ old('headers', $webhook->headers ? json_encode($webhook->headers, JSON_PRETTY_PRINT) : '') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary btn-sm pull-right">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-md-12">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Danger Zone</h3>
            </div>
            <div class="box-body">
                <form action="{{ route('admin.webhooks.view', $webhook->id) }}" method="POST" style="display: inline;">
                    {!! csrf_field() !!}
                    {!! method_field('DELETE') !!}
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this webhook?')">
                        <i class="fa fa-trash"></i> Delete Webhook
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Execution History</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>Event</th>
                            <th>Response Code</th>
                            <th class="text-center">Status</th>
                            <th>Error</th>
                            <th>Timestamp</th>
                        </tr>
                        @forelse ($executions as $execution)
                            <tr>
                                <td><span class="label label-primary">{{ $execution->event }}</span></td>
                                <td><code>{{ $execution->response_code ?? 'N/A' }}</code></td>
                                <td class="text-center">
                                    @if($execution->successful)
                                        <span class="label label-success">Success</span>
                                    @else
                                        <span class="label label-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($execution->error, 100) }}</td>
                                <td>{{ $execution->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No executions recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($executions->hasPages())
                <div class="box-footer text-center">
                    {{ $executions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pType').select2({minimumResultsForSearch: -1});
        $('#pEnabled').select2({minimumResultsForSearch: -1});
        $('#pEvents').select2({placeholder: 'Select events...'});

        function toggleHeaders() {
            if ($('#pType').val() === 'discord') {
                $('#headersSection').slideUp();
            } else {
                $('#headersSection').slideDown();
            }
        }
        $('#pType').on('change', toggleHeaders);
        toggleHeaders();
    </script>
@endsection
