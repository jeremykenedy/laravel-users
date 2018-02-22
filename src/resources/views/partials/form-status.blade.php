@if (session('message'))
  <div class="alert alert-primary alert-dismissable">
    <h4><i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i></h4>
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    {{ session('message') }}
  </div>
@endif

@if (session('success'))
  <div class="alert alert-success alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <h4><i class="icon fa fa-check fa-fw" aria-hidden="true"></i> Success</h4>
    {{ session('success') }}
  </div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissable" role="alert">
  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  <h4 class="alert-heading">
    <i class="fas fa-exclamation-triangle fa-fw" aria-hidden="true"></i>
    Error
  </h4>
  <hr>
  <p class="mb-0">
    {{ session('error') }}
  </p>
</div>
@endif

@if (count($errors) > 0)
  <div class="alert alert-danger alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <h4>
      <i class="icon fa fa-warning fa-fw" aria-hidden="true"></i>
      <strong>Error</strong>  Please check check the following:
    </h4>
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
