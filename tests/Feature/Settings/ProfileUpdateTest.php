<?php

test('profile page is displayed', function () {
    $user = superAdmin();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = superAdmin();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = superAdmin();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('validation errors surfaced by a real request are in Indonesian', function () {
    // Bukti end-to-end AC-04 / PRD ADM-02: pesan yang benar-benar sampai ke
    // browser, bukan hanya hasil Validator::make di isolation.
    $user = superAdmin();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => '',
            'email' => 'bukan-email',
        ]);

    $response
        ->assertSessionHasErrors(['name', 'email'])
        ->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'email' => 'Email harus berupa alamat email yang valid.',
        ])
        ->assertRedirect(route('profile.edit'));
});
