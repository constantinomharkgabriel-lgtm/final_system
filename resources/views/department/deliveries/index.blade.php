@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Deliveries</h1>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5>Delivery List</h5>
        </div>
        <div class="card-body">
            @if ($deliveries->isEmpty())
                <p>No deliveries found.</p>
            @else
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tracking Number</th>
                            <th>Recipient Name</th>
                            <th>Delivery Address</th>
                            <th>Scheduled Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
                            <tr>
                                <td>{{ $delivery->tracking_number }}</td>
                                <td>{{ $delivery->recipient_name }}</td>
                                <td>{{ $delivery->delivery_address }}</td>
                                <td>{{ $delivery->scheduled_date->format('Y-m-d') }}</td>
                                <td>{{ ucfirst($delivery->status) }}</td>
                                <td>
                                    <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-primary btn-sm">View</a>
                                    <a href="{{ route('deliveries.edit', $delivery->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $deliveries->links() }}
            @endif
        </div>
    </div>
</div>
@endsection