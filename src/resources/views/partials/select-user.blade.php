@if(\jeremykenedy\laravelusers\Support\UserAccess::selectable(isset($deleted) ? $deleted : (method_exists($user, 'trashed') && $user->trashed())) && (!Auth::check() || (string) Auth::id() !== (string) $user->getKey()))
<input type="checkbox" data-lu-select value="{{ $user->getKey() }}" aria-label="{{ __('laravelusers::ui.select_user', ['name' => $user->name]) }}">
@endif
