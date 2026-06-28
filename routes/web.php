<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

// Auth
Route::view('/login', 'auth.login')->name('auth.login');
Route::view('/register', 'auth.register')->name('auth.register');

// Host
Route::view('/dashboard', 'host.dashboard')->name('host.dashboard');
Route::view('/quiz/editor', 'host.quiz-editor')->name('host.quiz-editor');
Route::view('/host/lobby', 'host.lobby')->name('host.lobby');
Route::view('/host/game', 'host.game')->name('host.game');
Route::view('/host/results', 'host.results')->name('host.results');

// Participant
Route::view('/join', 'participant.join')->name('participant.join');
Route::view('/participant/lobby', 'participant.lobby')->name('participant.lobby');
Route::view('/participant/question', 'participant.question')->name('participant.question');
Route::view('/participant/result', 'participant.result')->name('participant.result');
Route::view('/participant/final', 'participant.final')->name('participant.final');
