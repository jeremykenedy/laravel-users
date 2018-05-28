<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="{{ config('app.locale') }}">
  <head>
    <meta charset="UTF-8">
    <title>{{ $page_title or "QRM" }}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
	<link rel="shortcut icon" href="{{ asset("/vendor/qrm-vf/vf/images/favicon.png") }}">
    <!-- Bootstrap 3.3.2 -->
    <link href="{{ asset("/vendor/qrm-vf/bower_components/bootstrap/dist/css/bootstrap.min.css") }}" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{asset("/vendor/qrm-vf/bower_components/font-awesome/web-fonts-with-css/css/fontawesome-all.css")}}"/> 
    <!-- Theme style -->
    <link href="{{ asset("/vendor/qrm-vf/bower_components/admin-lte/dist/css/AdminLTE.min.css")}}" rel="stylesheet" type="text/css" />
    <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
          page. However, you can choose any other skin. Make sure you
          apply the skin class to the body tag so the changes take effect.
    -->
    <link href="{{ asset("/vendor/qrm-vf/bower_components/admin-lte/dist/css/skins/skin-blue.min.css")}}" rel="stylesheet" type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
    @stack('css')
  </head>
  <body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

      <!-- Header -->
      @include('QrmViews::partials.header')

      <!-- Sidebar -->
      @include('QrmViews::partials.sidebar')

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            {{ $page_title or null }}
            <small>{{ $page_description or null }}</small>
          </h1>
          <!-- You can dynamically generate breadcrumbs here 
          <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
          </ol> -->
        </section>

        <!-- Main content -->
        <section class="content">          
          <!-- Your Page Content Here -->
		   @if ($errors->any())
			<div class="alert alert-danger">
				<ul>
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		  @endif
		  @include('flash::message')
          @yield('content')
        </section><!-- /.content -->
         @yield('modals')
      </div><!-- /.content-wrapper -->

      <!-- Footer -->
      @include('QrmViews::partials.footer')

    </div><!-- ./wrapper -->
    
    <script>
        function LA() {}
        LA.token = "{{ csrf_token() }}";
    </script>
    <!-- REQUIRED JS SCRIPTS -->

    <!-- jQuery 2.1.3 -->
    <script src="{{ asset ("/vendor/qrm-vf/bower_components/jquery/dist/jquery.min.js") }}" type="text/javascript"></script>
    <script src="{{ asset ("/vendor/qrm-vf/bower_components/bootstrap/dist/js/bootstrap.min.js") }}" type="text/javascript"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset ("/vendor/qrm-vf/bower_components/admin-lte/dist/js/adminlte.min.js") }}" type="text/javascript"></script>
	
	<script>
		$('div.alert').not('.alert-important').delay(3000).fadeOut(350);
		$.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
	</script>
    <!-- Optionally, you can add Slimscroll and FastClick plugins. 
          Both of these plugins are recommended to enhance the 
          user experience -->
    @stack('scripts')
  </body>
</html>