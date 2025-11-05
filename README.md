# Classifieds Backend

Laravel 11 ve Sanctum tabanlı bu proje, ilanların yayınlandığı bir ilan yönetim sistemi API'si sunar. Kullanıcılar kategori ve alt kategoriler altında ilan oluşturabilir, yöneticiler ise ilan onay sürecini yönetebilir.

## İçindekiler
- [Önkoşullar](#önkoşullar)
- [Kurulum](#kurulum)
  - [Yerel geliştirme](#yerel-geliştirme)
  - [Docker ile çalışma](#docker-ile-çalışma)
- [Veritabanı ve depolama](#veritabanı-ve-depolama)
- [Kimlik doğrulama](#kimlik-doğrulama)
- [API endpointleri](#api-endpointleri)
- [Testler](#testler)

## Önkoşullar
- PHP 8.2+ (Docker imajı PHP 8.4 kullanır)
- Composer 2.x
- Node.js 18+ (yalnızca frontend derlemesi gerekiyorsa)
- MySQL 8 (varsayılan değerler `.env.example` içinde)
- Opsiyonel: Docker ve Docker Compose

## Kurulum
`git clone` işleminden sonra proje dizinine geçerek aşağıdaki adımları takip edin.

### Yerel geliştirme
1. Bağımlılıkları yükleyin:
   ```bash
   composer install
   npm install # yalnızca gerekliyse
   ```
2. Ortam dosyasını oluşturun:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. `.env` dosyasında veritabanı ve Sanctum ayarlarını kendi ortamınıza göre güncelleyin.
4. Migrasyonları ve seed'leri çalıştırın:
   ```bash
   php artisan migrate --seed
   ```
5. Depolama linkini oluşturun (ilan görselleri için gereklidir):
   ```bash
   php artisan storage:link
   ```
6. Uygulamayı çalıştırın:
   ```bash
   php artisan serve
   ```
   API tabanı varsayılan olarak `http://localhost:8000/api`’dir.

### Docker ile çalışma
1. Docker servislerini ayağa kaldırın:
   ```bash
   docker compose up -d
   ```
2. PHP konteynerine girerek bağımlılıkları yükleyin ve kurulum adımlarını tamamlayın:
   ```bash
   docker compose exec php-fpm composer install
   docker compose exec php-fpm php artisan key:generate
   docker compose exec php-fpm php artisan migrate --seed
   docker compose exec php-fpm php artisan storage:link
   ```
3. Nginx portu `4000` olarak ayarlanmıştır. API tabanı `http://localhost:4000/api` olacaktır.

## Veritabanı ve depolama
- Seed dosyaları (`CategorySeeder`, `SubcategorySeeder`) kategori ve alt kategori örneklerini otomatik oluşturur.
- Kullanıcı seed’i bulunmadığından yöneticiyi manuel oluşturmanız gerekir. `users` tablosundaki kaydın `is_admin` alanını `1` yaparak yönetici yetkisi verebilirsiniz.
- Görseller `storage/app/public/listings` içine yüklenir ve `public/storage` üzerinden servis edilir. Üretimde uygun dosya sistemi sürücüsünü (`FILESYSTEM_DISK`) ayarlamayı unutmayın.

## Kimlik doğrulama
- API, [Laravel Sanctum](https://laravel.com/docs/sanctum) kullanır.
- `register` ve `login` endpointleri giriş/üye olma işlemlerini gerçekleştirir ve Bearer Token döner.
- Yetki gerektiren tüm endpointlere istek atarken `Authorization: Bearer <token>` başlığını ekleyin.
- `logout` endpointi aktif token’ları iptal eder.
- `is_admin=true` olan kullanıcılar yönetici operasyonlarını çağırabilir.

## API endpointleri

| Metot | URL | Açıklama | Kimlik Doğrulama | Not |
|-------|-----|----------|------------------|-----|
| `POST` | `/api/register` | Yeni kullanıcı oluşturur | Gerekmez | `name`, `email`, `password`, `password_confirmation` |
| `POST` | `/api/login` | Giriş yapar ve token üretir | Gerekmez | `email`, `password` |
| `POST` | `/api/logout` | Aktif token’ı iptal eder | Gerekir | Bearer token |
| `GET` | `/api/categories` | Kategorileri alt kategorileriyle listeler | Gerekmez | |
| `GET` | `/api/categories/{id}` | Tek kategori detayını döner | Gerekmez | |
| `POST` | `/api/categories` | Yeni kategori oluşturur | Admin gerekir | `name` |
| `PUT/PATCH` | `/api/categories/{id}` | Kategori günceller | Admin gerekir | `name` |
| `DELETE` | `/api/categories/{id}` | Kategori ve alt kategorilerini siler | Admin gerekir | |
| `GET` | `/api/subcategories` | Alt kategorileri listeler | Gerekmez | `category_id` query parametresi opsiyonel |
| `GET` | `/api/subcategories/{id}` | Alt kategori detayını döner | Gerekmez | |
| `POST` | `/api/subcategories` | Yeni alt kategori oluşturur | Admin gerekir | `name`, `category_id` |
| `PUT/PATCH` | `/api/subcategories/{id}` | Alt kategori günceller | Admin gerekir | `name`, `category_id` (opsiyonel) |
| `DELETE` | `/api/subcategories/{id}` | Alt kategori siler | Admin gerekir | |
| `GET` | `/api/listings` | Onaylanmış ilanları sayfalı listeler | Gerekir | `status=approved` filtreli döner |
| `GET` | `/api/listings/{id}` | İlan detayını döner | Gerekir | |
| `POST` | `/api/listings` | Yeni ilan oluşturur | Gerekir | `title`, `description`, `category_id`, `subcategory_id`, `city`, `district`, `price`, `image` (dosya) |
| `PUT/PATCH` | `/api/listings/{id}` | İlan günceller (sahibi) | Gerekir | Dosya güncellemesi için `image` yüklenebilir |
| `DELETE` | `/api/listings/{id}` | İlanı siler (sahibi) | Gerekir | |
| `GET` | `/api/admin/listings/pending` | Onay bekleyen ilanları listeler | Admin gerekir | |
| `POST` | `/api/admin/listings/{id}/approve` | İlanı onaylar | Admin gerekir | |
| `POST` | `/api/admin/listings/{id}/reject` | İlanı reddeder | Admin gerekir | |

### İlan oluşturma/güncelleme notları
- `image` alanı zorunludur ve `multipart/form-data` isteğiyle gönderilmelidir.
- Oluşturulan ilanlar varsayılan olarak `pending` durumundadır. Yalnızca onaylanan ilanlar (`approved`) herkese açık listelerde görünür.
- Tekil ilanlara erişim yetkisi token gerektirir; kullanıcı yalnızca kendi ilanını güncelleyebilir/silebilir.

## Testler
Örnek testler `tests` klasöründe yer alır. Tüm testleri çalıştırmak için:
```bash
php artisan test
```

## Katkıda bulunma
Hataları GitHub issue’ları üzerinden bildirebilir veya pull request açabilirsiniz. Yeni özellik eklerken ilgili testleri yazmanız önerilir.
