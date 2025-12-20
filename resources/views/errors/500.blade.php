@extends('layouts.error')

@section('title', 'Something Went Wrong')
@section('code', '')

@section('message')
    <div class="text-center">
        <div class="mb-4">
            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
        </div>
        <h2 class="h3 mb-3">Something Went Wrong</h2>

        @if(isset($exception) && $exception->getMessage())
            <div class="alert alert-danger text-start mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Error:</strong> {{ $exception->getMessage() }}
            </div>
        @else
            <p class="text-muted mb-4">
                We encountered an unexpected error while processing your request.
            </p>
        @endif

        <p class="text-muted small mb-4">
            Our team has been notified and is working to resolve this issue.
            Please try again in a few moments.
        </p>

        @if(config('app.debug') && isset($exception))
            <div class="alert alert-secondary text-start mb-4" style="font-size: 0.8rem;">
                <h6 class="alert-heading">Debug Information:</h6>
                <p class="mb-1"><strong>File:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
                <details>
                    <summary class="cursor-pointer">Stack Trace</summary>
                    <pre class="mb-0 mt-2" style="font-size: 0.7rem; max-height: 300px; overflow: auto;">{{ $exception->getTraceAsString() }}</pre>
                </details>
            </div>
        @endif

        <div class="mt-4">
            <a href="{{ url()->previous() }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Go Back
            </a>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-house"></i> Home
            </a>
        </div>
    </div>
@endsection
