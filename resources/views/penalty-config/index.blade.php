@extends('layouts.app')

@section('title', 'Penalty Configuration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Penalty Configuration</h3>
                    <p class="card-text">Configure penalty settings for overdue book returns</p>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('penalty-config.update') }}" method="POST">
                        @csrf
                        @method('POST')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="penalty_enabled" class="form-label">
                                        <input type="checkbox" 
                                               name="penalty_enabled" 
                                               id="penalty_enabled" 
                                               value="1"
                                               {{ $config->penalty_enabled ? 'checked' : '' }}
                                               class="form-check-input me-2">
                                        Enable Penalty System
                                    </label>
                                    <small class="form-text text-muted">
                                        Enable or disable the penalty system for overdue returns
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active" class="form-label">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               id="is_active" 
                                               value="1"
                                               {{ $config->is_active ? 'checked' : '' }}
                                               class="form-check-input me-2">
                                        Configuration Active
                                    </label>
                                    <small class="form-text text-muted">
                                        Make this configuration active for the system
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="grace_period_penalty_days" class="form-label">Grace Period Penalty Days</label>
                                    <input type="number" 
                                           name="grace_period_penalty_days" 
                                           id="grace_period_penalty_days" 
                                           class="form-control"
                                           value="{{ $config->grace_period_penalty_days }}"
                                           min="0" 
                                           max="30" 
                                           required>
                                    <small class="form-text text-muted">
                                        Number of days before penalty is applied (0-30 days)
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="penalty_multiplier" class="form-label">Penalty Multiplier</label>
                                    <input type="number" 
                                           name="penalty_multiplier" 
                                           id="penalty_multiplier" 
                                           class="form-control"
                                           value="{{ $config->penalty_multiplier }}"
                                           min="1" 
                                           max="10" 
                                           step="0.1"
                                           required>
                                    <small class="form-text text-muted">
                                        Multiplier for penalty calculation (1.0-10.0)
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Penalty Calculation Preview</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <div class="badge badge-primary mb-2">Normal Fine</div>
                                                    <div class="h5">Rp 10,000</div>
                                                    <small class="text-muted">Original fine amount</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <div class="badge badge-warning mb-2">Grace Period</div>
                                                    <div class="h5">{{ $config->grace_period_penalty_days }} days</div>
                                                    <small class="text-muted">Before penalty applies</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <div class="badge badge-danger mb-2">Penalty Fine</div>
                                                    <div class="h5">Rp {{ number_format(10000 * $config->penalty_multiplier, 0, ',', '.') }}</div>
                                                    <small class="text-muted">After grace period</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Configuration
                                </button>
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection