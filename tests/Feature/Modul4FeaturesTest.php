<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Modul4FeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin@test.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        $this->mahasiswa = User::create([
            'name'     => 'Mahasiswa Test',
            'email'    => 'mahasiswa@test.com',
            'password' => Hash::make('password'),
            'role'     => 'mahasiswa',
        ]);
    }

    public function test_mahasiswa_cannot_access_admin_dashboard_or_routes(): void
    {
        $response = $this->actingAs($this->mahasiswa)->get('/dashboard');
        $response->assertStatus(403);

        $responseBuku = $this->actingAs($this->mahasiswa)->get('/buku');
        $responseBuku->assertStatus(403);
    }

    public function test_admin_can_upload_book_with_cover(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('cover.jpg', 600, 800);

        $response = $this->actingAs($this->admin)->post('/buku', [
            'judul'        => 'Laravel Modul 4',
            'penulis'      => 'Penulis Hebat',
            'penerbit'     => 'Penerbit IT',
            'tahun_terbit' => 2024,
            'stok'         => 10,
            'cover'        => $file,
        ]);

        $response->assertRedirect('/buku');

        $buku = Buku::where('judul', 'Laravel Modul 4')->first();
        $this->assertNotNull($buku);
        $this->assertNotNull($buku->cover);

        Storage::disk('public')->assertExists($buku->cover);
    }

    public function test_admin_can_update_book_cover_and_delete_old_cover(): void
    {
        Storage::fake('public');

        $oldFile = UploadedFile::fake()->image('old_cover.jpg');
        $oldPath = $oldFile->store('buku', 'public');

        $buku = Buku::create([
            'judul'        => 'Buku Cover Test',
            'penulis'      => 'Penulis',
            'penerbit'     => 'Penerbit',
            'tahun_terbit' => 2022,
            'stok'         => 5,
            'cover'        => $oldPath,
        ]);

        Storage::disk('public')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->image('new_cover.png');

        $response = $this->actingAs($this->admin)->put("/buku/{$buku->id}", [
            'judul'        => 'Buku Cover Test Updated',
            'penulis'      => 'Penulis',
            'penerbit'     => 'Penerbit',
            'tahun_terbit' => 2022,
            'stok'         => 5,
            'cover'        => $newFile,
        ]);

        $response->assertRedirect('/buku');

        $bukuFresh = $buku->fresh();
        $this->assertNotEquals($oldPath, $bukuFresh->cover);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($bukuFresh->cover);
    }

    public function test_deleting_book_deletes_cover_from_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('cover_to_delete.jpg');
        $path = $file->store('buku', 'public');

        $buku = Buku::create([
            'judul'        => 'Buku Delete',
            'penulis'      => 'Penulis',
            'penerbit'     => 'Penerbit',
            'tahun_terbit' => 2023,
            'stok'         => 2,
            'cover'        => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($this->admin)->delete("/buku/{$buku->id}");
        $response->assertRedirect('/buku');

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('buku', ['id' => $buku->id]);
    }

    public function test_home_katalog_search_filter_sort(): void
    {
        Buku::create([
            'judul'        => 'Pemrograman Web Tingkat Lanjut',
            'penulis'      => 'John Doe',
            'penerbit'     => 'Gramedia',
            'tahun_terbit' => 2021,
            'stok'         => 5,
        ]);

        Buku::create([
            'judul'        => 'Struktur Data dan Algoritma',
            'penulis'      => 'Jane Smith',
            'penerbit'     => 'Informatika',
            'tahun_terbit' => 2023,
            'stok'         => 0,
        ]);

        // Search test
        $res = $this->get('/?search=Tingkat+Lanjut');
        $res->assertStatus(200);
        $res->assertSee('Pemrograman Web Tingkat Lanjut');
        $res->assertDontSee('Struktur Data dan Algoritma');

        // Status filter: tersedia
        $resTersedia = $this->get('/?status=tersedia');
        $resTersedia->assertStatus(200);
        $resTersedia->assertSee('Pemrograman Web Tingkat Lanjut');
        $resTersedia->assertDontSee('Struktur Data dan Algoritma');

        // Status filter: habis
        $resHabis = $this->get('/?status=habis');
        $resHabis->assertStatus(200);
        $resHabis->assertDontSee('Pemrograman Web Tingkat Lanjut');
        $resHabis->assertSee('Struktur Data dan Algoritma');
    }
}
