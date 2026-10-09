@if($avatarSourceEnabled ?? false)
    <div class="{{ $modern ? 'lu-field' : 'form-group row' }}">
        <label for="avatar_source" class="lu-icon-label lu-control-title {{ $modern ? '' : 'col-md-3 control-label' }}">@unless($accountPage ?? false)<span class="lu-title-icon lu-title-icon-avatar">@include('laravelusers::partials.icon', ['name' => 'user'])</span>@endunless<span>{{ __('laravelusers::ui.avatar_source') }}</span></label>
        <div class="{{ $modern ? 'lu-control' : 'col-md-9' }}">
            <div class="{{ $modern ? 'lu-input-group' : 'input-group' }}">
                <select id="avatar_source" name="avatar_source" class="{{ $modern ? 'lu-input form-select' : 'form-control custom-select' }}" @if(!$avatarSourceAvailable) disabled @endif @if($errors->has('avatar_source')) aria-invalid="true" aria-describedby="avatar_source-error" @endif>
                    @foreach(array_merge(['inherit'], \jeremykenedy\laravelusers\Support\Avatar::SOURCES) as $source)<option value="{{ $source }}" @if(old('avatar_source', $avatarSource ?? 'inherit') === $source) selected @endif>{{ __('laravelusers::ui.avatar_source_'.$source) }}</option>@endforeach
                </select>
                @if($modern && config('laravelusers.iconsEnabled', true))<label class="lu-input-icon" for="avatar_source">@include('laravelusers::partials.icon', ['name' => 'user'])</label>@elseif(!$modern && config('laravelusers.fontAwesomeEnabled', true))<div class="input-group-append"><label for="avatar_source" class="input-group-text mb-0"><i class="fa fa-fw fa-user" aria-hidden="true"></i></label></div>@endif
            </div>
            <p class="lu-muted text-muted">{{ __($avatarSourceAvailable ? 'laravelusers::ui.avatar_source_hint' : 'laravelusers::ui.avatar_migration_required') }}</p>
            @if($errors->has('avatar_source'))<p id="avatar_source-error" class="lu-field-error text-danger">{{ $errors->first('avatar_source') }}</p>@endif
        </div>
    </div>
@endif
