@props(['page'])
@if($page['data']['appearance_preview'] ?? null)
    <div class="lu-native-cards" data-lu-native-appearance-previews>
        @foreach($page['data']['appearance_preview']['avatars'] as $kind => $sample)
            <section class="lu-settings-choice"><h3>{{ $page['labels']['settings_'.$kind.'_color'] }}</h3><div class="lu-profile-identity lu-native-profile" data-lu-native-preview-card="{{ $kind }}"><span data-lu-native-preview-avatar>@if($sample['avatar'])<x-laravelusers::livewire.avatar :avatar="$sample['avatar']" />@endif</span><div><h4>{{ $sample['name'] }}</h4><p>{{ $page['labels']['appearance_avatar_preview'] }}</p></div></div></section>
        @endforeach
    </div>
@endif
