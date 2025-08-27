<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Services\TemplateService;

beforeEach(function () {
    $this->viewFactory = $this->mock(ViewFactory::class);
    $this->config = [
        'cache_enabled' => false,
        'cache_ttl' => 3600,
    ];
    $this->templateService = new TemplateService($this->viewFactory, $this->config);
});

test('renders notification message when no template is provided', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message without template'
    );

    $result = $this->templateService->render($notification);

    expect($result)->toBe('Test message without template');
});

test('renders template when template is provided and exists', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Fallback message',
        options: [
            'template' => 'welcome',
            'template_data' => ['name' => 'John Doe'],
        ]
    );

    $view = $this->mock(View::class);
    $view->shouldReceive('render')->once()->andReturn('Rendered template content');

    $this->viewFactory
        ->shouldReceive('exists')
        ->with('notifications.welcome')
        ->once()
        ->andReturn(true);

    $this->viewFactory
        ->shouldReceive('make')
        ->with('notifications.welcome', \Mockery::type('array'))
        ->once()
        ->andReturn($view);

    $result = $this->templateService->render($notification);

    expect($result)->toBe('Rendered template content');
});

test('falls back to message when template does not exist', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Fallback message',
        options: [
            'template' => 'nonexistent',
        ]
    );

    $this->viewFactory
        ->shouldReceive('exists')
        ->with('notifications.nonexistent')
        ->once()
        ->andReturn(false);

    $result = $this->templateService->render($notification);

    expect($result)->toBe('Fallback message');
});

test('sanitizes template path to prevent path traversal', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Fallback message',
        options: [
            'template' => '../../../malicious',
        ]
    );

    $this->viewFactory
        ->shouldReceive('exists')
        ->with('notifications....malicious')
        ->once()
        ->andReturn(false);

    $result = $this->templateService->render($notification);

    expect($result)->toBe('Fallback message');
});

test('passes correct data to template', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message',
        options: [
            'subject' => 'Test Subject',
            'template' => 'welcome',
            'template_data' => ['name' => 'John Doe', 'company' => 'Acme Corp'],
        ]
    );

    $view = $this->mock(View::class);
    $view->shouldReceive('render')->once()->andReturn('Rendered content');

    $this->viewFactory
        ->shouldReceive('exists')
        ->with('notifications.welcome')
        ->once()
        ->andReturn(true);

    $this->viewFactory
        ->shouldReceive('make')
        ->with('notifications.welcome', \Mockery::any())
        ->once()
        ->andReturn($view);

    $result = $this->templateService->render($notification);

    expect($result)->toBe('Rendered content');
});
