<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    @if(Session::has('downloadFile'))
        <meta http-equiv="refresh" content="5;url={{ Session::get('downloadFile') }}">

        {{ Session::forget('downloadFile') }}
    @endif
    <meta name="_token" content="{{csrf_token()}}" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Childminder</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="/assets/vendors/iconfonts/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="/assets/vendors/iconfonts/ionicons/dist/css/ionicons.css">
    <link rel="stylesheet" href="/assets/vendors/iconfonts/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="/assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="/assets/vendors/css/vendor.bundle.addons.css">
    <!-- endinject -->
    <!-- plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="/assets/css/shared/style.css">
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="/assets/css/demo_1/style.css">
    <!-- End Layout styles -->
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

</head>
<body>

<div class="container-scroller">
    <!-- partial:../../partials/_navbar.html -->
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-top justify-content-center">
            <a class="navbar-brand brand-logo" href="../../index.html">
               CHILDMINDER </a>
            <a class="navbar-brand brand-logo-mini" href="../../index.html">
                CHILDMINDER </a>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-center">


            <ul class="navbar-nav ml-auto">



            </ul>
            <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                <span class="mdi mdi-menu"></span>
            </button>
        </div>
    </nav>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
        <!-- partial:../../partials/_sidebar.html -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
            <ul class="nav">
                <li class="nav-item nav-profile">
                    <a href="#" class="nav-link">
                        <div class="profile-image">
{{--                            <img class="img-xs rounded-circle" src="../../assets/images/faces/face8.jpg" alt="profile image">--}}
                            <div class="dot-indicator bg-success"></div>
                        </div>
                        <div class="text-wrapper">
                            <p class="profile-name">Admin</p>
{{--                            <p class="designation">Premium user</p>--}}
                        </div>
                    </a>
                </li>
                <li class="nav-item nav-category">Main Menu</li>
                <li class="nav-item">
                    <a class="nav-link" href="/home">
                        <i class="menu-icon typcn typcn-document-text"></i>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="/generateQrcode">
                        <i class="menu-icon typcn typcn-document-text"></i>
                        <span class="menu-title">Generate Qrcode</span>
                    </a>
                </li>
                <li class="nav-item">



                <a class="nav-link" href="#"  onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        <i class="menu-icon typcn typcn-document-text"></i>
                        <span class="menu-title">Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        {{ csrf_field() }}
                    </form>
                </li>

            </ul>
        </nav>
        <!-- partial -->
        <div class="main-panel">
            <div class="content-wrapper">
            @yield('content')

            </div>
            <!-- content-wrapper ends -->
            <!-- partial:../../partials/_footer.html -->
            <footer class="footer">
                <div class="container-fluid clearfix">
                    <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © 2020 <a href="#" target="_blank">Childminder</a>. All rights reserved.</span>
                </div>
            </footer>
            <!-- partial -->
        </div>
        <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
</div>
<!-- container-scroller -->
<!-- plugins:js -->
<script src="/assets/vendors/js/vendor.bundle.base.js"></script>
<script src="/assets/vendors/js/vendor.bundle.addons.js"></script>
<!-- endinject -->
<!-- Plugin js for this page-->
<!-- End plugin js for this page-->
<!-- inject:js -->
<script src="/assets/js/shared/off-canvas.js"></script>
<script src="/assets/js/shared/misc.js"></script>
<!-- endinject -->
<!-- Custom js for this page-->
<script src="/assets/js/shared/jquery.cookie.js" type="text/javascript"></script>
<!-- End custom js for this page-->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.js"></script>

<script>
    $(document).ready( function () {
        $('#table_id').DataTable();
    } );
</script>
<script>
    function deleteUser(id) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: '/users/destroy',
                    type: 'POST',
                    data: { id:id },
                    statusCode: {
                        500: function() {
                            Swal.fire(

                                'Oops Something Went Wrong!',
                                'error'
                            )
                        }
                    },
                    success: function(response){
                        var table = $('#table_id').DataTable();

                        if(response.status = 200){
                            // Remove row from HTML Table
                            $('#del'+id).closest('tr').css('background','#0056b3');
                            $('#del'+id).closest('tr').fadeOut(800,function(){
                                $(this).remove();
                                table
                                    .row( $(el).closest('tr') )
                                    .remove()
                                    .draw();
                            });
                            Swal.fire(

                                'User has been deleted successfully!',
                                'success'
                            )
                        }else{
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            })
                        }

                    }
                });

            }
        })


    }
</script>
</body>
</html>
