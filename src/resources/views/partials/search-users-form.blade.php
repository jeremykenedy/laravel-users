<div class="row">
    <div class="col-sm-8 offset-sm-4 col-md-6 offset-md-6 col-lg-5 offset-lg-7 col-xl-4 offset-xl-8">
        {!! Form::open(['route' => 'search-users', 'method' => 'POST', 'role' => 'form', 'class' => 'needs-validation', 'id' => 'search_users']) !!}
            {!! csrf_field() !!}
            <div class="input-group mb-3">
                {!! Form::text('user_search_box', NULL, ['id' => 'user_search_box', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.search-users-ph'), 'aria-label' => trans('laravelusers::forms.search-users-ph'), 'required' => false]) !!}
                <div class="input-group-append">
                    <a href="#" class="btn btn-warning clear-search" data-toggle="tooltip" title="{!! trans('laravelusers::laravelusers.tooltips.clear-search') !!}">
                        @if(config('laravelusers.fontAwesomeEnabled'))
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="sr-only">
                                {!! trans('laravelusers::laravelusers.tooltips.clear-search') !!}
                            </span>
                        @else
                            {!! trans('laravelusers::laravelusers.tooltips.clear-search') !!}
                        @endif
                    </a>
                    @if(config('laravelusers.fontAwesomeEnabled'))
                        {!! Form::button('<i class="fas fa-search" aria-hidden="true"></i> <span class="sr-only"> ' . trans('laravelusers::laravelusers.tooltips.submit-search') . ' </span>', ['class' => 'btn btn-secondary', 'type' => 'submit', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => trans('laravelusers::laravelusers.tooltips.submit-search')]) !!}
                    @else
                        {!! Form::button(trans('laravelusers::laravelusers.tooltips.submit-search'), ['class' => 'btn btn-secondary', 'type' => 'submit', 'title' => trans('laravelusers::laravelusers.tooltips.submit-search')]) !!}
                    @endif
                </div>
            </div>
        {!! Form::close() !!}
    </div>
</div>

