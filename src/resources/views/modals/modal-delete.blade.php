<div class="modal fade modal-danger" id="confirmDelete" role="dialog" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">
          @lang('laravelusers::modals.delete_user_title')
        </h4>
      </div>
      <div class="modal-body">
        <p>
          @lang('laravelusers::modals.delete_user_message')
        </p>
      </div>
      <div class="modal-footer">
        {!! Form::button(trans('laravelusers::modals.delete_user_btn_cancel'), array('class' => 'btn btn-outline pull-left btn-flat', 'type' => 'button', 'data-dismiss' => 'modal' )) !!}
        {!! Form::button(trans('laravelusers::modals.delete_user_btn_confirm'), array('class' => 'btn btn-danger pull-right btn-flat', 'type' => 'button', 'id' => 'confirm' )) !!}
      </div>
    </div>
  </div>
</div>
