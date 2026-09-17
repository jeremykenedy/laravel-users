@if(!Auth::check() || (string) Auth::id() !== (string) $user->id)
    <form method="POST" action="{{ route('user.destroy', $user->id) }}" data-lu-confirm="{{ __('laravelusers::ui.confirm_delete', ['name' => $user->name]) }}">
        @csrf
        @method('DELETE')
        <button class="lu-button lu-danger" type="submit">{{ __('laravelusers::ui.delete') }}</button>
    </form>
@endif
