<div id="szn-preloader">
    <style>
        #szn-preloader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            overflow: hidden;
            background: #090b10;
        }

        /* The ES spinner, the same in every ES app (es-boot.css and the page-change loader in es-apps.js) */
        #szn-preloader:before {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            box-sizing: border-box;
            width: 36px;
            height: 36px;
            margin: -18px 0 0 -18px;
            border: 3px solid rgba(59, 130, 246, .22);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: animate-preloader .8s linear infinite;
        }

        @-webkit-keyframes animate-preloader {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes animate-preloader {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>

</div>