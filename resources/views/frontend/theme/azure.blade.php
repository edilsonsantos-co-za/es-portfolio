<!--
=========================================================
* Template Name: Personal - v4.7.0 (Azure Blue Theme)
* Template URL: https://bootstrapmade.com/personal-free-resume-bootstrap-template/
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
=========================================================
-->

@php
    $accentColor = $portfolioConfig['accentColor'];
    $accentColorRGB = Utils::getRgbValue($accentColor);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    @include('common.googleAnalytics')
    @if (!empty($portfolioConfig['script']['header']) && $portfolioConfig['script']['header'] != '')
        <script>
            {!!$portfolioConfig['script']['header']!!}
        </script>
    @endif

    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta property="og:title" content="{{$portfolioConfig['seo']['title']}}"/>
    <meta property="title" content="{{$portfolioConfig['seo']['title']}}"/>
    <meta name="description" content="{{$portfolioConfig['seo']['description']}}" />
    <meta property="og:description" content="{{$portfolioConfig['seo']['description']}}"/>
    <meta name="author" content="{{$portfolioConfig['seo']['author']}}" />
    <meta property="og:image" content="{{ media_url($portfolioConfig['seo']['image']) }}" />
    <meta property="og:image:secure_url" content="{{ media_url($portfolioConfig['seo']['image']) }}" />

    <title>{{$about->name}}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ Utils::getFavicon() }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/common/lib/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/common/lib/fontawesome/css/all.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/common/lib/mdi-icon/css/materialdesignicons.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/common/lib/boxicons/css/boxicons.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/common/lib/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/common/lib/iziToast/css/iziToast.min.css') }}" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="{{ asset('assets/themes/azure/css/styles.css') }}" rel="stylesheet">

    <style>
        /* Dynamic background image from cover photo */
        body::before {
            @if($about->cover)
                background: #040404 url("{{ media_url($about->cover) }}") top right no-repeat !important;
                background-size: cover !important;
            @endif
        }

        /* Dynamic accent color overrides */
        a {
            color: {{$accentColor}} !important;
        }

        a:hover {
            color: rgba({{$accentColorRGB}}, 0.8) !important;
        }

        #header h2 span {
            border-bottom-color: {{$accentColor}} !important;
        }

        #header .social-links a {
            transition: all 0.3s ease !important;
            color: #fff !important;
        }

        #header .social-links a i {
            color: #fff !important;
        }

        #header .social-links a:hover {
            background: {{$accentColor}} !important;
            transform: scale(1.15) !important;
            box-shadow: 0 0 10px rgba({{$accentColorRGB}}, 0.5) !important;
            color: #fff !important;
        }

        #header .social-links a:hover i {
            color: #fff !important;
        }

        /* Keep social links visible and align them in header-top mode */
        #header.header-top .social-links {
            display: flex !important;
            margin-top: 0 !important;
            margin-left: auto !important;
        }

        #header.header-top .container {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        #header.header-top .navbar {
            margin-left: auto !important;
            margin-right: 20px !important;
        }

        .navbar a:before {
            background-color: {{$accentColor}} !important;
        }

        .section-title h2::after {
            background: {{$accentColor}} !important;
        }

        .about-me .content h3 {
            color: {{$accentColor}} !important;
        }

        .about-me .content ul i {
            color: {{$accentColor}} !important;
        }

        .counts .count-box i {
            color: {{$accentColor}} !important;
        }

        .skills .progress-bar {
            background-color: {{$accentColor}} !important;
        }

        .resume-item h4 {
            color: {{$accentColor}} !important;
        }

        .resume-item h5 {
            background: rgba({{$accentColorRGB}}, 0.1) !important;
            color: {{$accentColor}} !important;
        }

        .services .icon-box .icon {
            color: {{$accentColor}} !important;
        }

        .services .icon-box:hover {
            border-color: {{$accentColor}} !important;
        }

        .portfolio #portfolio-flters li:hover,
        .portfolio #portfolio-flters li.filter-active {
            background: {{$accentColor}} !important;
        }

        .contact .info-box i {
            color: {{$accentColor}} !important;
        }

        .contact .php-email-form button[type=submit] {
            background: {{$accentColor}} !important;
        }

        .contact .php-email-form button[type=submit]:hover {
            background: rgba({{$accentColorRGB}}, 0.8) !important;
        }

        .contact .php-email-form input:focus,
        .contact .php-email-form textarea:focus {
            border-color: {{$accentColor}} !important;
        }

        /* Contact section social links - white by default */
        .contact .social-links a {
            color: #fff !important;
        }

        .contact .social-links a i {
            color: #fff !important;
        }

        .contact .social-links a:hover {
            color: {{$accentColor}} !important;
        }

        .contact .social-links a:hover i {
            color: {{$accentColor}} !important;
        }

        /* React projects styling */
        .text-primary {
            color: {{$accentColor}} !important;
        }

        /* Progress bar for skills with custom styling */
        .skill-progress-wrapper .progress-bar {
            background: {{$accentColor}} !important;
        }

        /* Force all text to be white for readability against background image */
        section .container,
        section .container *,
        .about .container,
        .about .container *,
        .about-me .content h3,
        .about-me .content p,
        .about-me .content ul,
        .about-me .content ul li,
        .about-me .content ul li strong,
        .about-me .content ul li span,
        .section-title h2,
        .section-title p,
        .skills .section-title h2,
        .skills .section-title p,
        .skills .progress .skill,
        .skills .progress .skill .val,
        h1, h2, h3, h4, h5, h6,
        p, li, span, div,
        .resume .section-title h2,
        .resume .section-title p,
        .services .section-title h2,
        .services .section-title p,
        .portfolio .section-title h2,
        .portfolio .section-title p,
        .contact .section-title h2,
        .contact .section-title p,
        .resume-title,
        .resume-item p,
        .resume-item em,
        .services .icon-box h4,
        .services .icon-box p,
        .contact .info-box h3,
        .contact .info-box p {
            color: #fff !important;
        }

        /* Override any inherited colors */
        section * {
            color: #fff !important;
        }

        /* Specific overrides for form elements and special cases */
        .php-email-form .form-control,
        .php-email-form input,
        .php-email-form textarea,
        .php-email-form select,
        #contact-me-form .form-control,
        #contact-me-form input,
        #contact-me-form textarea,
        #contact-me-form select {
            color: #333 !important;
            background: rgba(255, 255, 255, 0.9) !important;
        }

        /* Form labels and placeholders */
        .php-email-form .form-control::placeholder,
        #contact-me-form .form-control::placeholder {
            color: #666 !important;
        }

        /* Form button text */
        .php-email-form button[type="submit"],
        #contact-me-form button[type="submit"] {
            color: #fff !important;
        }

        /* Ant Design text components - force black text with higher specificity */
        section .ant-card,
        section .ant-card *,
        section .ant-card-body,
        section .ant-card-body *,
        section .ant-card-meta,
        section .ant-card-meta *,
        section .ant-card-meta-title,
        section .ant-card-meta-title *,
        section .ant-card-meta-description,
        section .ant-card-meta-description *,
        section .ant-card-head,
        section .ant-card-head *,
        section .ant-card-head-title,
        section .ant-card-head-title *,
        section .ant-radio-button,
        section .ant-radio-button *,
        section .ant-radio-button-wrapper,
        section .ant-radio-button-wrapper *,
        section .ant-radio-button-wrapper span,
        section .ant-radio-group,
        section .ant-radio-group *,
        div .ant-card,
        div .ant-card *,
        div .ant-card-body,
        div .ant-card-body *,
        div .ant-card-meta-title,
        div .ant-card-meta-title *,
        div .ant-radio-button,
        div .ant-radio-button * {
            color: #333 !important;
            background-color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Specific card styling */
        .ant-card {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
        }

        /* Ensure layout components stay transparent */
        .ant-row,
        .ant-col {
            background-color: transparent !important;
        }

        /* Ant Design drawer components - transparent/semi-transparent background */
        .ant-drawer-mask {
            background: transparent !important;
        }

        .ant-drawer,
        .ant-drawer-content-wrapper,
        .ant-drawer-wrapper-body {
            background: transparent !important;
        }

        .ant-drawer-content {
            background: rgba(0, 0, 0, 0.9) !important;
        }

        .ant-drawer-body,
        .ant-drawer-header,
        .ant-drawer-footer {
            background: rgba(0, 0, 0, 0.9) !important;
            color: #fff !important;
        }

        /* Drawer header and footer specific styling */
        .ant-drawer-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .ant-drawer-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Drawer close button */
        .ant-drawer-close {
            color: #fff !important;
        }

        /* Drawer title */
        .ant-drawer-title {
            color: #fff !important;
        }

        /* Drawer buttons styling */
        .ant-drawer .ant-btn,
        .ant-drawer .ant-btn-primary,
        .ant-drawer .ant-btn-default,
        .ant-drawer button {
            background: rgba(0, 0, 0, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #fff !important;
        }

        .ant-drawer .ant-btn:hover,
        .ant-drawer .ant-btn-primary:hover,
        .ant-drawer .ant-btn-default:hover,
        .ant-drawer button:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            color: #fff !important;
        }

        /* Primary buttons in drawer */
        .ant-drawer .ant-btn-primary {
            background: {{$accentColor}} !important;
            border-color: {{$accentColor}} !important;
            color: #fff !important;
        }

        .ant-drawer .ant-btn-primary:hover {
            background: rgba({{$accentColorRGB}}, 0.8) !important;
            border-color: rgba({{$accentColorRGB}}, 0.8) !important;
            color: #fff !important;
        }

        /* Drawer text elements */
        .ant-drawer-body *,
        .ant-drawer p,
        .ant-drawer span,
        .ant-drawer div,
        .ant-drawer a {
            color: #fff !important;
        }

        .ant-drawer a:hover {
            color: {{$accentColor}} !important;
        }

        /* Header text - Portfolio owner name and tagline */
        #header h1,
        #header h1 a,
        #header h1 a:hover,
        #header h2,
        #header h2 span {
            color: #fff !important;
        }

        /* Navigation link texts */
        #navbar ul li a,
        #navbar ul li a:hover,
        #navbar ul li a:focus,
        #navbar ul li a.active,
        .navbar a,
        .navbar a:hover,
        .navbar a:focus,
        .navbar .active,
        .navbar .nav-link,
        .navbar .nav-link:hover,
        .navbar .nav-link:focus,
        .navbar .nav-link.active,
        .nav-link,
        .nav-link:hover,
        .nav-link:focus,
        .nav-link.active {
            color: #fff !important;
        }

        /* Mobile nav links */
        .navbar-mobile a,
        .navbar-mobile a:hover,
        .navbar-mobile a:focus,
        .navbar-mobile a.active {
            color: #fff !important;
        }

        /* Mobile nav toggle button */
        .mobile-nav-toggle {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.1) !important;
            font-size: 28px !important;
            cursor: pointer !important;
            display: none !important;
            line-height: 0 !important;
            transition: 0.5s !important;
            position: fixed !important;
            right: 25px !important;
            top: 5px !important;
            z-index: 9998 !important;
            padding: 10px 15px !important;
            border-radius: 50px !important;
            width: 50px !important;
            height: 50px !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
        }

        @media (max-width: 991px) {
            .mobile-nav-toggle {
                display: flex !important;
            }

            .navbar ul {
                display: none !important;
            }

            .navbar ul.navbar-mobile {
                position: fixed !important;
                overflow: hidden !important;
                top: 0 !important;
                right: 0 !important;
                left: 0 !important;
                bottom: 0 !important;
                background: rgba(0, 0, 0, 0.9) !important;
                transition: 0.3s !important;
                z-index: 999 !important;
                display: block !important;
                padding-top: 60px !important;
            }

            .navbar-mobile ul {
                display: block !important;
                position: absolute !important;
                top: 55px !important;
                right: 15px !important;
                bottom: 45px !important;
                left: 15px !important;
                padding: 10px 0 !important;
                overflow-y: auto !important;
                border: 2px solid rgba(255, 255, 255, 0.2) !important;
            }

            .navbar-mobile li {
                padding: 12px 20px !important;
            }
        }

        /* Fix responsive layout for service icon boxes */
        .services .row {
            display: flex !important;
            flex-wrap: wrap !important;
            margin: 0 -15px !important;
        }

        .services .row > div {
            flex: 0 0 auto !important;
            padding: 0 15px !important;
            margin-bottom: 20px !important;
        }

        .services .icon-box {
            width: 100% !important;
            height: 100% !important;
            min-height: 200px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
            padding: 30px 20px !important;
            background: rgba(255, 255, 255, 0.08) !important;
            border-radius: 10px !important;
            transition: all 0.3s ease !important;
        }

        .services .icon-box .icon {
            margin-bottom: 20px !important;
        }

        .services .icon-box h4 {
            margin-bottom: 15px !important;
            flex-shrink: 0 !important;
        }

        .services .icon-box p {
            flex-grow: 1 !important;
            margin-bottom: 0 !important;
        }

        /* Responsive breakpoints for service boxes */
        @media (max-width: 767px) {
            .services .row > div {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .services .row > div {
                flex: 0 0 50% !important;
                max-width: 50% !important;
            }
        }

        @media (min-width: 992px) {
            .services .row > div {
                flex: 0 0 33.333333% !important;
                max-width: 33.333333% !important;
            }
        }
    </style>
</head>

<body>
    @include('common.preloader2')

    <!-- ======= Header ======= -->
    <header id="header">
        <div class="container">
            <h1><a href="#header">{{ $about->name }}</a></h1>
            @if ($about->taglines)
                <h2>I'm a passionate <span id="typed-strings-header"></span></h2>
            @else
                <h2>{{ $about->description }}</h2>
            @endif

            <nav id="navbar" class="navbar">
                <ul>
                    <li><a class="nav-link active" href="#header">Home</a></li>
                    @if ($portfolioConfig['visibility']['about'])
                        <li><a class="nav-link" href="#about">About</a></li>
                    @endif
                    @if ($portfolioConfig['visibility']['experiences'] || $portfolioConfig['visibility']['education'])
                        <li><a class="nav-link" href="#resume">Resume</a></li>
                    @endif
                    @if ($portfolioConfig['visibility']['services'])
                        <li><a class="nav-link" href="#services">Services</a></li>
                    @endif
                    @if ($portfolioConfig['visibility']['projects'])
                        <li><a class="nav-link" href="#portfolio">Portfolio</a></li>
                    @endif
                    @if ($portfolioConfig['visibility']['contact'])
                        <li><a class="nav-link" href="#contact">Contact</a></li>
                    @endif
                </ul>
                <i class="bx bx-menu mobile-nav-toggle"></i>
            </nav>

            @if ($about->social_links)
                <div class="social-links">
                    @foreach (safe_json_decode($about->social_links) as $social)
                        <a href="{{$social->link}}" target="_blank">
                            <i class="{{$social->iconClass}}"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </header><!-- End Header -->

    @if ($portfolioConfig['visibility']['about'])
        <!-- ======= About Section ======= -->
        <section id="about" class="about">
            <div class="container">
                <div class="section-title">
                    <h2>About</h2>
                    <p>Learn more about me</p>
                </div>

                <div class="row">
                    <div class="col-lg-4" data-aos="fade-right">
                        <img src="{{ media_url($about->avatar) }}" class="img-fluid" alt="{{ $about->name }}">
                    </div>
                    <div class="col-lg-8 pt-4 pt-lg-0 content" data-aos="fade-left">
                        <h3>Professional Profile</h3>
                        <p class="fst-italic">{{ $about->description }}</p>

                        <div class="row">
                            <div class="col-lg-6">
                                <ul>
                                    @if ($about->address)
                                        <li><i class="bx bx-chevron-right"></i> <strong>Location:</strong> <span>{{ $about->address }}</span></li>
                                    @endif
                                    @if ($about->phone)
                                        <li><i class="bx bx-chevron-right"></i> <strong>Phone:</strong> <span>{{ $about->phone }}</span></li>
                                    @endif
                                    @if ($about->email)
                                        <li><i class="bx bx-chevron-right"></i> <strong>Email:</strong> <span>{{ $about->email }}</span></li>
                                    @endif
                                </ul>
                            </div>
                            <div class="col-lg-6">
                                <ul>
                                    @if ($portfolioConfig['visibility']['cv'])
                                        <li><i class="bx bx-chevron-right"></i> <strong>CV:</strong> <span><a href="{{ media_url($about->cv) }}" download>Download Resume</a></span></li>
                                    @endif
                                    <li><i class="bx bx-chevron-right"></i> <strong>Available:</strong> <span>Yes</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($portfolioConfig['visibility']['skills'])
                    <!-- ======= Skills ======= -->
                    <div class="skills container">
                        <div class="section-title">
                            <h2>Skills</h2>
                        </div>

                        <div class="row">
                            @if (!empty($skills))
                                @foreach ($skills as $skill)
                                    <div class="col-lg-6">
                                        <div class="progress">
                                            <span class="skill">{{ $skill->name }} <i class="val">{{ $skill->proficiency }}%</i></span>
                                            <div class="progress-bar-wrap">
                                                <div class="progress-bar" role="progressbar" aria-valuenow="{{ $skill->proficiency }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $skill->proficiency }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div><!-- End Skills -->
                @endif
            </div>
        </section><!-- End About Section -->
    @endif

    @if ($portfolioConfig['visibility']['experiences'] || $portfolioConfig['visibility']['education'])
        <!-- ======= Resume Section ======= -->
        <section id="resume" class="resume">
            <div class="container">
                <div class="section-title">
                    <h2>Resume</h2>
                    <p>Check My Resume</p>
                </div>

                <div class="row">
                    @if ($portfolioConfig['visibility']['education'] && $education)
                        <div class="col-lg-6">
                            <h3 class="resume-title">Education</h3>
                            @foreach ($education as $value)
                                <div class="resume-item">
                                    <h4>{{ $value->degree }}</h4>
                                    @if ($value->period)
                                        <h5>{{ $value->period }}</h5>
                                    @endif
                                    <p><em>{{ $value->institution }}</em></p>
                                    @if ($value->department)
                                        <p>{{ $value->department }}</p>
                                    @endif
                                    @if ($value->cgpa)
                                        <p>{{ $value->cgpa }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($portfolioConfig['visibility']['experiences'] && $experiences)
                        <div class="col-lg-6">
                            <h3 class="resume-title">Professional Experience</h3>
                            @foreach ($experiences as $experience)
                                <div class="resume-item">
                                    <h4>{{ $experience->position }}</h4>
                                    @if ($experience->period)
                                        <h5>{{ $experience->period }}</h5>
                                    @endif
                                    <p><em>{{ $experience->company }}</em></p>
                                    @if ($experience->details)
                                        <div>{!! $experience->details !!}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section><!-- End Resume Section -->
    @endif

    @if ($portfolioConfig['visibility']['services'])
        <!-- ======= Services Section ======= -->
        <section id="services" class="services">
            <div class="container">
                <div class="section-title">
                    <h2>Services</h2>
                    <p>My Services</p>
                </div>

                <div class="row">
                    @if (!empty($services))
                        @foreach ($services as $service)
                            <div class="col-lg-4 col-md-6 d-flex align-items-stretch mt-4">
                                <div class="icon-box w-100">
                                    <div class="icon"><i class="{{ $service->icon }}"></i></div>
                                    <h4><a href="">{{ $service->title }}</a></h4>
                                    <p>{{ $service->details }}</p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </section><!-- End Services Section -->
    @endif

    @if ($portfolioConfig['visibility']['projects'])
        <!-- ======= Portfolio Section ======= -->
        <section id="portfolio" class="portfolio">
            <div class="container">
                <div class="section-title">
                    <h2>Portfolio</h2>
                    <p>My Works</p>
                </div>

                <div
                    id="react-project-root"
                    data-accentcolor="{{$accentColor}}"
                    data-demomode="{{$demoMode}}"
                />
            </div>
        </section><!-- End Portfolio Section -->
    @endif

    @if ($portfolioConfig['visibility']['contact'])
        <!-- ======= Contact Section ======= -->
        <section id="contact" class="contact">
            <div class="container">
                <div class="section-title">
                    <h2>Contact</h2>
                    <p>Contact Me</p>
                </div>

                <div class="row">
                    @if ($about->address)
                        <div class="col-lg-6">
                            <div class="info-box">
                                <i class="bx bx-map"></i>
                                <h3>My Address</h3>
                                <p>{{ $about->address }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($about->social_links)
                        <div class="col-lg-6">
                            <div class="info-box">
                                <i class="bx bx-share-alt"></i>
                                <h3>Social Profiles</h3>
                                <div class="social-links">
                                    @foreach (safe_json_decode($about->social_links) as $social)
                                        <a href="{{$social->link}}" target="_blank">
                                            <i class="{{$social->iconClass}}"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($about->email)
                        <div class="col-lg-6">
                            <div class="info-box">
                                <i class="bx bx-envelope"></i>
                                <h3>Email Me</h3>
                                <p>{{ $about->email }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($about->phone)
                        <div class="col-lg-6">
                            <div class="info-box">
                                <i class="bx bx-phone-call"></i>
                                <h3>Call Me</h3>
                                <p>{{ $about->phone }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <form action="#" id="contact-me-form" method="POST" class="php-email-form mt-4">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" name="body" rows="5" placeholder="Message" required></textarea>
                    </div>
                    <div class="my-3">
                        <div class="loading">Loading</div>
                        <div class="error-message"></div>
                        <div class="sent-message">Your message has been sent. Thank you!</div>
                    </div>
                    <div class="text-center"><button type="submit">Send Message</button></div>
                </form>
            </div>
        </section><!-- End Contact Section -->
    @endif

    <!-- Vendor JS Files -->
    <script src="{{ asset('assets/common/lib/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/common/lib/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/common/lib/jquery.easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('assets/common/lib/typed/typed.js') }}"></script>
    <script src="{{ asset('assets/common/lib/aos/aos.js') }}"></script>
    <script src="{{ asset('assets/common/lib/iziToast/js/iziToast.min.js') }}"></script>
    <script src="{{ asset('assets/common/lib/jquery-validation/jquery.validate.min.js') }}"></script>

    <script>
        $(function() {
            if ($('#szn-preloader').length) {
                $('#szn-preloader').delay(100).fadeOut('slow', function() {
                    $(this).remove();
                });
            }

            // Initialize page state - default to Home view
            $('#header').removeClass('header-top');
            $('section').removeClass('section-show');
            $('.nav-link[href="#header"]').addClass('active');
            $('.nav-link').not('[href="#header"]').removeClass('active');

            AOS.init();

            // Typed.js for header
            if ($('#typed-strings-header').length) {
                @if($about->taglines)
                    var typedStrings = new Typed('#typed-strings-header', {
                        strings: {!! json_encode(safe_json_decode($about->taglines)) !!},
                        typeSpeed: 70,
                        backSpeed: 40,
                        smartBackspace: true,
                        loop: true
                    });
                @endif
            }

            // Navigation scrolling
            $(document).on('click', '.nav-link', function(e) {
                e.preventDefault();
                let target = $(this).attr('href');

                if (target.indexOf('#') === 0) {
                    if (target === '#header') {
                        // Show full header (home view)
                        $('#header').removeClass('header-top');
                        $('section').removeClass('section-show');
                    } else {
                        // Show section and move header to top
                        $('#header').addClass('header-top');
                        $('section').removeClass('section-show');
                        $(target).addClass('section-show');
                    }

                    // Update active navigation
                    $('.nav-link').removeClass('active');
                    $(this).addClass('active');

                    // Close mobile menu if open
                    $('.navbar ul').removeClass('navbar-mobile');
                    $('.mobile-nav-toggle').removeClass('bx-x').addClass('bx-menu');
                }
            });

            // Mobile nav toggle
            $('.mobile-nav-toggle').click(function() {
                $('.navbar ul').toggleClass('navbar-mobile');
                $(this).toggleClass('bx-menu bx-x');
            });

            // Form validation and submission
            $('#contact-me-form').validate({
                rules: {
                    name: { required: true },
                    email: { required: true, email: true },
                    subject: { required: true },
                    body: { required: true }
                },
                submitHandler: function(form, event) {
                    event.preventDefault();
                    const submitButton = $('#contact-me-form button[type="submit"]');

                    submitButton.attr('disabled', true);
                    submitButton.html('Sending...');

                    $.ajax({
                        url: '{!! route('contact-me') !!}',
                        dataType: 'json',
                        data: $('#contact-me-form').serialize(),
                        type: 'post',
                        success: function(response) {
                            if (response.status === 200) {
                                showNotification(response.message, 'success', false);
                                $('#contact-me-form').trigger('reset');
                            } else {
                                showNotification(response.message, 'error', false);
                            }
                        },
                        error: function(jqXHR, exception) {
                            showNotification('Something went wrong', 'error', false);
                        },
                        complete: function() {
                            submitButton.attr('disabled', false);
                            submitButton.html('Send Message');
                        }
                    });
                }
            });

            function showNotification(message = 'Something went wrong', type = 'error', sticky = false) {
                iziToast.show({
                    title: '',
                    message: message,
                    messageSize: 12,
                    position: 'topRight',
                    theme: 'dark',
                    pauseOnHover: true,
                    timeout: sticky === false ? 5000 : false,
                    progressBarColor: type === 'success' ? '#00ffb8' : '#ffafb4',
                    color: type === 'success' ? '#565c70' : '#565c70',
                    messageColor: type === 'success' ? '#00ffb8' : '#ffafb4',
                    icon: type === 'success' ? 'fas fa-check' : 'fas fa-times-circle'
                });
            }
        });
    </script>

    <!-- Portfolio Projects JS -->
    <script src="{{ asset('js/client/frontend/roots/projects.js') }}"></script>

    @if (!empty($portfolioConfig['script']['footer']) && $portfolioConfig['script']['footer'] != '')
        <script>
            {!!$portfolioConfig['script']['footer']!!}
        </script>
    @endif
    @include('common.pixelTracking')
</body>
</html>
