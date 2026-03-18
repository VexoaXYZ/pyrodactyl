@extends('layouts.admin')

@section('title')
    New Webhook
@endsection

@section('content-header')
    <h1>New Webhook<small>Create a new webhook configuration.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.webhooks') }}">Webhooks</a></li>
        <li class="active">New</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.webhooks') }}" method="POST">
    {!! csrf_field() !!}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Webhook Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pType" class="form-label">Type</label>
                                <select name="type" id="pType" class="form-control">
                                    <option value="regular" @if(old('type', 'regular') === 'regular') selected @endif>Regular</option>
                                    <option value="discord" @if(old('type') === 'discord') selected @endif>Discord</option>
                                </select>
                                <p class="text-muted small">Discord webhooks will automatically format payloads as Discord embeds.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pEnabled" class="form-label">Enabled</label>
                                <select name="is_enabled" id="pEnabled" class="form-control">
                                    <option value="1" @if(old('is_enabled', '1') == '1') selected @endif>Yes</option>
                                    <option value="0" @if(old('is_enabled') == '0') selected @endif>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pEndpoint" class="form-label">Endpoint URL</label>
                        <input type="url" name="endpoint" id="pEndpoint" class="form-control" value="{{ old('endpoint') }}" placeholder="https://discord.com/api/webhooks/... or https://example.com/webhook" />
                        <p class="text-muted small">The URL that will receive the webhook POST request.</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">Description</label>
                        <input type="text" name="description" id="pDescription" class="form-control" value="{{ old('description') }}" placeholder="Optional description" />
                    </div>
                    <div class="form-group">
                        <label for="pEvents" class="form-label">Events</label>
                        <select name="events[]" id="pEvents" class="form-control" multiple>
                            <option value="server:created" @if(is_array(old('events')) && in_array('server:created', old('events'))) selected @endif>server:created</option>
                            <option value="server:updated" @if(is_array(old('events')) && in_array('server:updated', old('events'))) selected @endif>server:updated</option>
                            <option value="server:deleted" @if(is_array(old('events')) && in_array('server:deleted', old('events'))) selected @endif>server:deleted</option>
                        </select>
                        <p class="text-muted small">Select which events should trigger this webhook.</p>
                    </div>
                    <div id="headersSection">
                        <div class="form-group">
                            <label for="pHeaders" class="form-label">Custom Headers (JSON)</label>
                            <textarea name="headers" id="pHeaders" class="form-control" rows="3" placeholder='{"Authorization": "Bearer token"}'>{{ old('headers') }}</textarea>
                            <p class="text-muted small">Optional JSON object of custom HTTP headers to send with the request. Not used for Discord webhooks.</p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-success btn-sm pull-right">Create Webhook</button>
                </div>
            </div>
        </div>
    </div>
</form>
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
