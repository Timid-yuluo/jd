@php
    $errorCode = '503';
    $errorTitle = '系统维护中';
    $errorMessage = '系统正在进行维护升级，请稍后回来访问。维护通常在几分钟内完成。';
    $errorIcon = 'ti ti-tools';
    $errorCssFile = 'errors-503';
    $errorAction = '<a href="/" class="error-link"><i class="ti ti-refresh me-2"></i>刷新重试</a> <a href="/" class="error-link"><i class="ti ti-home me-2"></i>返回首页</a>';
@endphp
@extends('layouts.error')
