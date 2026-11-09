<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Features\Profile\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_terisi(): void
    {
        $user = $this->makeMember(['name' => 'Budi', 'username' => 'budi']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSeeLivewire(Profile::class);

        Livewire::actingAs($user)->test(Profile::class)
            ->assertSet('name', 'Budi')
            ->assertSet('username', 'budi');
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_ubah_data(): void
    {
        $user = $this->makeMember();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('name', 'Nama Baru')
            ->set('username', 'baru')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSee('Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'username' => 'baru']);
    }

    public function test_username_harus_unik_tapi_boleh_milik_sendiri(): void
    {
        $this->makeMember(['username' => 'dipakai']);
        $user = $this->makeMember(['username' => 'saya']);

        $component = Livewire::actingAs($user)->test(Profile::class);

        $component->set('username', 'dipakai')->call('saveProfile')->assertHasErrors(['username' => 'unique']);
        $component->set('username', 'saya')->call('saveProfile')->assertHasNoErrors();
    }

    public function test_ubah_password(): void
    {
        $user = $this->makeMember(['password' => 'lama123']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'lama123')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertSee('Password berhasil diubah.');

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_ubah_password_validasi(): void
    {
        $user = $this->makeMember(['password' => 'lama123']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasErrors(['current_password']);
    }

    public function test_foto_profil_upload_ganti_dan_hapus(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();

        $component = Livewire::actingAs($user)->test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('a.jpg'))
            ->call('savePhoto')
            ->assertHasNoErrors()
            ->assertSee('Foto profil berhasil diperbarui.');

        $first = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $component->set('photo', UploadedFile::fake()->image('b.jpg'))->call('savePhoto');
        $second = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $component->call('removePhoto')->assertSee('Foto profil dihapus.');
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_foto_harus_gambar(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->makeMember())->test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('savePhoto')
            ->assertHasErrors(['photo']);
    }

    public function test_service_menghapus_foto_tanpa_foto(): void
    {
        $user = $this->makeMember();

        (new ProfileService)->removePhoto($user);

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_layout_menampilkan_foto_profil(): void
    {
        Storage::fake('public');
        $user = $this->makeMember(['profile_photo_path' => 'profile-photos/x.jpg']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('profile-photos/x.jpg', false);
    }
}
