<div class="modal fade modal-danger" id="confirmDelete" role="dialog" aria-labelledby="confirmDeleteLabel" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {!! trans('laravelusers::modals.delete_user_title') !!}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">close</span>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    {!! trans('laravelusers::modals.delete_user_message') !!}
                </p>
            </div>
            <div class="modal-footer">
                {!! Form::button(trans('laravelusers::modals.delete_user_btn_cancel'), array('class' => 'btn btn-light pull-left', 'type' => 'button', 'data-dismiss' => 'modal' )) !!}
                {!! Form::button(trans('laravelusers::modals.delete_user_btn_confirm'), array('class' => 'btn btn-danger pull-right btn-flat', 'type' => 'button', 'id' => 'confirm' )) !!}
            </div>
        </div>
    </div>
</div>
