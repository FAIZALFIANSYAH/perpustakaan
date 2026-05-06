@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="row">
    @if(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Librarian'))
        <!-- Admin Dashboard -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total_books'] }}</h3>
                    <p>Total Books</p>
                </div>
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['total_borrowings'] }}</h3>
                    <p>Total Borrowings</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['overdue_borrowings'] }}</h3>
                    <p>Overdue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($stats['unpaid_fines']) }}</h3>
                    <p>Unpaid Fines</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill"></i>
                </div>
            </div>
        </div>
    @else
        <!-- Member Dashboard -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['my_borrowings'] }}</h3>
                    <p>My Borrowings</p>
                </div>
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['active_borrowings'] }}</h3>
                    <p>Active</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['overdue_borrowings'] }}</h3>
                    <p>Overdue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($stats['unpaid_fines']) }}</h3>
                    <p>My Fines</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill"></i>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Borrowings</h3>
            </div>
            <div class="card-body">
                @if($stats['recent_borrowings']->count() > 0)
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Books</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_borrowings'] as $borrowing)
                                <tr>
                                    <td>{{ $borrowing->id }}</td>
                                    <td>{{ $borrowing->member->name }}</td>
                                    <td>
                                        @foreach($borrowing->items as $item)
                                            {{ $item->book->title }}<br>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $borrowing->status === 'borrowed' ? 'primary' : ($borrowing->status === 'overdue' ? 'danger' : 'success') }}">
                                            {{ ucfirst($borrowing->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $borrowing->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No recent borrowings found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection