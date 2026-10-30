import { ImageOff } from 'lucide-react';
import {
    useState,
    type CSSProperties,
    type HTMLAttributes,
    type ImgHTMLAttributes,
    type ReactNode,
    type SourceHTMLAttributes,
} from 'react';

/**
 * Responsive image source untuk elemen <picture>.
 *
 * Gunakan properti `type` untuk format gambar seperti AVIF atau WebP,
 * dan `media` untuk menentukan source berdasarkan media query.
 *
 * Browser akan memilih source yang paling sesuai dengan kondisi perangkat.
 */
export type ImageSource = Pick<
    SourceHTMLAttributes<HTMLSourceElement>,
    'type' | 'media' | 'sizes'
> & {
    /**
     * Satu atau beberapa kandidat gambar beserta descriptor resolusinya.
     *
     * Contoh:
     * "/images/hero-640.webp 640w, /images/hero-1280.webp 1280w"
     */
    srcSet: string;
};

/**
 * Props tambahan untuk container pembungkus gambar.
 *
 * `className` dan `style` dikelola langsung melalui props utama Image
 * agar konfigurasi styling container memiliki satu sumber kebenaran.
 */
export type ImageWrapperProps = Omit<
    HTMLAttributes<HTMLDivElement>,
    'children'
>;

/**
 * Properti komponen Image.
 *
 * Komponen ini mempertahankan sebagian besar atribut native <img>,
 * termasuk crossOrigin, referrerPolicy, draggable, dan data attributes.
 *
 * Beberapa atribut dikelola secara khusus untuk menghindari konflik
 * antara konfigurasi layout, fallback, dan strategi loading.
 */
export type ImageProps = Omit<
    ImgHTMLAttributes<HTMLImageElement>,
    | 'src'
    | 'alt'
    | 'width'
    | 'height'
    | 'srcSet'
    | 'sizes'
    | 'loading'
    | 'fetchPriority'
    | 'decoding'
    | 'className'
    | 'style'
    | 'onLoad'
    | 'onError'
> & {
    /**
     * URL gambar utama.
     *
     * Bisa berupa URL lokal, URL absolut, atau URL dari media storage.
     */
    src: string;

    /**
     * Teks alternatif untuk aksesibilitas.
     *
     * Gunakan deskripsi yang menjelaskan isi atau fungsi gambar.
     * Gunakan string kosong (`alt=""`) jika gambar hanya dekoratif.
     */
    alt: string;

    /**
     * Lebar intrinsik gambar dalam piksel.
     *
     * Digunakan sebagai atribut HTML width, bukan sebagai batas
     * maksimum lebar visual gambar.
     *
     * Disarankan untuk membantu browser menghitung layout sebelum
     * gambar selesai dimuat.
     */
    width?: number;

    /**
     * Tinggi intrinsik gambar dalam piksel.
     *
     * Disarankan digunakan bersama `width` untuk membantu mencegah
     * layout shift saat gambar dimuat.
     */
    height?: number;

    /**
     * Kandidat gambar berdasarkan resolusi atau lebar viewport.
     *
     * Contoh:
     * "/images/news-640.webp 640w, /images/news-1280.webp 1280w"
     *
     * Biasanya digunakan bersama `sizes`.
     *
     * Catatan: atribut ini tidak menghasilkan file gambar baru.
     * Semua file kandidat harus tersedia di server.
     */
    srcSet?: string;

    /**
     * Ukuran visual gambar yang diharapkan pada kondisi viewport tertentu.
     *
     * Contoh:
     * "(max-width: 768px) 100vw, 50vw"
     *
     * Sangat berguna untuk menghindari browser mengunduh gambar
     * beresolusi terlalu besar pada perangkat mobile.
     */
    sizes?: string;

    /**
     * Daftar source alternatif untuk elemen <picture>.
     *
     * Berguna untuk format gambar modern seperti AVIF dan WebP,
     * serta variasi gambar berdasarkan media query.
     *
     * Source pertama yang kompatibel dan sesuai dengan kondisi browser
     * akan dipilih oleh browser.
     */
    sources?: readonly ImageSource[];

    /**
     * Rasio aspek container gambar.
     *
     * Bisa berupa nilai numerik atau CSS aspect-ratio.
     *
     * Contoh:
     * - 1
     * - 4 / 3
     * - 16 / 9
     * - "3 / 2"
     *
     * Jika tidak ditentukan, rasio dapat dihitung dari width dan height.
     */
    aspectRatio?: CSSProperties['aspectRatio'];

    /**
     * Strategi penyesuaian gambar terhadap container.
     *
     * - cover: memenuhi container, sebagian gambar mungkin terpotong.
     * - contain: seluruh gambar terlihat, mungkin menyisakan ruang kosong.
     * - fill: meregangkan gambar agar memenuhi container.
     * - none: mempertahankan ukuran intrinsik gambar.
     * - scale-down: memilih ukuran antara none dan contain.
     *
     * Default: cover.
     */
    fit?: CSSProperties['objectFit'];

    /**
     * Menentukan posisi gambar di dalam container.
     *
     * Contoh:
     * - "center"
     * - "top"
     * - "50% 30%"
     *
     * Berguna untuk menentukan bagian gambar yang tetap terlihat
     * ketika menggunakan fit="cover".
     */
    objectPosition?: CSSProperties['objectPosition'];

    /**
     * Mengatur loading gambar.
     *
     * - lazy: gambar dimuat ketika mendekati viewport.
     * - eager: gambar segera dijadwalkan untuk dimuat.
     *
     * Default: lazy.
     *
     * Jika `priority` bernilai true, loading otomatis menjadi eager.
     */
    loading?: ImgHTMLAttributes<HTMLImageElement>['loading'];

    /**
     * Menandai gambar sebagai gambar prioritas tinggi.
     *
     * Ketika true:
     * - loading menggunakan eager.
     * - fetchPriority menggunakan high secara default.
     *
     * Gunakan secara selektif untuk gambar penting, misalnya gambar hero
     * yang menjadi Largest Contentful Paint (LCP).
     *
     * Jangan mengaktifkan properti ini untuk semua gambar.
     */
    priority?: boolean;

    /**
     * Prioritas pengunduhan gambar.
     *
     * - high: browser disarankan memprioritaskan gambar.
     * - low: browser disarankan menurunkan prioritas gambar.
     * - auto: browser menentukan prioritas.
     *
     * Default: high ketika priority=true, selain itu auto.
     *
     * Nilai eksplisit dari fetchPriority tetap digunakan.
     */
    fetchPriority?: ImgHTMLAttributes<HTMLImageElement>['fetchPriority'];

    /**
     * Strategi decoding gambar.
     *
     * Default: async.
     *
     * Browser tetap memiliki kendali terhadap proses rendering,
     * sehingga properti ini merupakan petunjuk, bukan jaminan.
     */
    decoding?: ImgHTMLAttributes<HTMLImageElement>['decoding'];

    /**
     * URL gambar pengganti ketika gambar utama gagal dimuat.
     *
     * Jika gambar pengganti juga gagal, komponen menampilkan fallback UI.
     *
     * Jangan samakan nilainya dengan `src`, karena tidak akan memberikan
     * fallback yang berbeda.
     */
    fallbackSrc?: string;

    /**
     * UI pengganti ketika gambar utama dan fallbackSrc gagal dimuat.
     *
     * Bisa berupa icon, ilustrasi, atau komponen React lainnya.
     *
     * Jika tidak diberikan, komponen menggunakan icon ImageOff.
     */
    fallback?: ReactNode;

    /**
     * Konten placeholder selama gambar belum berhasil dimuat.
     *
     * Placeholder dirender di atas gambar, sehingga dapat digunakan
     * untuk menampilkan skeleton atau loading indicator.
     *
     * Jika tidak diberikan, komponen tidak menampilkan placeholder.
     */
    placeholder?: ReactNode;

    /**
     * Posisi CSS container pembungkus gambar.
     *
     * Default: relative.
     */
    position?: 'relative' | 'absolute' | 'fixed' | 'sticky' | 'static';

    /**
     * Class Tailwind untuk container pembungkus gambar.
     *
     * Contoh:
     * "overflow-hidden rounded-xl bg-muted"
     */
    className?: string;

    /**
     * Class Tailwind khusus untuk elemen <img>.
     *
     * Contoh:
     * "transition-transform duration-300 hover:scale-105"
     */
    imageClassName?: string;

    /**
     * Inline style untuk container pembungkus gambar.
     *
     * Style layout internal seperti aspectRatio tetap disiapkan
     * oleh komponen. Nilai yang diberikan di sini dapat menimpanya.
     */
    style?: CSSProperties;

    /**
     * Inline style khusus untuk elemen <img>.
     *
     * Dapat digunakan untuk menyesuaikan tampilan gambar lebih lanjut.
     */
    imageStyle?: CSSProperties;

    /**
     * Properti tambahan untuk container pembungkus.
     *
     * Mendukung atribut native div, termasuk event handler dan data attributes.
     *
     * `children`, `className`, dan `style` tidak diperbolehkan di sini
     * karena struktur dan styling dasar dikelola oleh Image.
     */
    wrapperProps?: ImageWrapperProps;

    /**
     * Callback ketika gambar berhasil dimuat.
     *
     * Callback tetap dijalankan setelah status internal gambar diperbarui.
     */
    onLoad?: ImgHTMLAttributes<HTMLImageElement>['onLoad'];

    /**
     * Callback ketika gambar gagal dimuat.
     *
     * Callback tetap dijalankan ketika fallback image gagal dimuat.
     *
     * Event error tidak selalu berarti koneksi jaringan gagal.
     * Penyebabnya juga dapat berupa URL yang tidak valid atau respons
     * gambar yang tidak dapat didekode browser.
     */
    onError?: ImgHTMLAttributes<HTMLImageElement>['onError'];
};

/**
 * Menggabungkan class CSS tanpa memerlukan dependency tambahan.
 *
 * Fungsi ini tidak melakukan Tailwind class conflict resolution.
 * Gunakan class yang tidak saling bertentangan untuk hasil yang konsisten.
 */
function joinClasses(
    ...classes: Array<string | undefined | false | null>
): string {
    return classes.filter(Boolean).join(' ');
}

/**
 * Komponen internal untuk mengelola state loading dan error.
 *
 * Komponen publik Image menggunakan key berdasarkan konfigurasi sumber.
 * Dengan demikian, perubahan sumber gambar akan mereset state internal
 * tanpa perlu useEffect tambahan.
 */
function ImageInstance({
    src,
    alt,
    width,
    height,
    srcSet,
    sizes,
    sources,
    aspectRatio,
    fit = 'cover',
    objectPosition,
    loading = 'lazy',
    priority = false,
    fetchPriority,
    decoding = 'async',
    fallbackSrc,
    fallback,
    placeholder,
    className,
    imageClassName,
    style,
    position = 'relative',
    imageStyle,
    wrapperProps,
    onLoad,
    onError,
    ...imgProps
}: ImageProps) {
    /**
     * Menandakan bahwa gambar utama gagal dimuat.
     */
    const [useFallback, setUseFallback] = useState(false);

    /**
     * Menandakan bahwa fallback image juga gagal dimuat.
     */
    const [fallbackFailed, setFallbackFailed] = useState(false);

    /**
     * Menandakan bahwa gambar saat ini telah berhasil dimuat.
     */
    const [isLoaded, setIsLoaded] = useState(false);

    /**
     * Fallback hanya digunakan jika URL-nya berbeda dari gambar utama.
     * Ini mencegah percobaan memuat URL yang sama secara berulang.
     */
    const hasUsableFallback = Boolean(fallbackSrc) && fallbackSrc !== src;

    /**
     * Gambar yang benar-benar akan dirender.
     */
    const currentSrc = useFallback && hasUsableFallback ? fallbackSrc! : src;

    /**
     * Setelah gambar utama gagal, srcSet dan picture sources tidak
     * digunakan lagi agar browser tidak memilih kandidat gambar utama.
     */
    const currentSrcSet = useFallback ? undefined : srcSet;
    const currentSizes = useFallback ? undefined : sizes;
    const currentSources = useFallback ? undefined : sources;

    /**
     * Jika fallback image gagal, tampilkan fallback UI.
     */
    const hasFailed = fallbackFailed || (useFallback && !hasUsableFallback);

    /**
     * Priority mengalahkan loading="lazy" karena gambar prioritas
     * harus segera dijadwalkan untuk dimuat.
     */
    const effectiveLoading = priority ? 'eager' : loading;

    /**
     * Nilai fetchPriority eksplisit tetap dihormati.
     */
    const effectiveFetchPriority =
        fetchPriority ?? (priority ? 'high' : 'auto');

    /**
     * Rasio aspek container:
     * 1. Gunakan aspectRatio eksplisit jika tersedia.
     * 2. Jika tidak, hitung dari width dan height.
     * 3. Jika keduanya tidak tersedia, biarkan gambar menentukan tinggi.
     */
    const resolvedAspectRatio =
        aspectRatio ?? (width && height ? `${width} / ${height}` : undefined);

    const hasFixedRatio = resolvedAspectRatio !== undefined;

    /**
     * Style dasar container.
     *
     * Container menggunakan aspect-ratio agar ruang gambar dapat
     * disiapkan sebelum gambar selesai diunduh.
     */
    const wrapperStyle: CSSProperties = {
        ...(hasFixedRatio
            ? {
                  aspectRatio: resolvedAspectRatio,
                  position: position,
                  overflow: 'hidden',
              }
            : {}),
        ...style,
    };

    /**
     * Style dasar gambar.
     *
     * Jika container memiliki rasio aspek, gambar memenuhi container.
     * Jika tidak, tinggi gambar mengikuti rasio intrinsiknya.
     *
     * imageStyle diberikan terakhir agar pengguna dapat menyesuaikan
     * style internal bila diperlukan.
     */
    const resolvedImageStyle: CSSProperties = {
        display: 'block',
        width: '100%',
        height: hasFixedRatio ? '100%' : 'auto',
        objectFit: fit,
        objectPosition,
        ...(hasFixedRatio
            ? {
                  position: 'absolute',
                  inset: 0,
              }
            : {}),
        ...imageStyle,
    };

    /**
     * Menangani keberhasilan pemuatan gambar.
     */
    const handleLoad: NonNullable<ImageProps['onLoad']> = (event) => {
        setIsLoaded(true);
        onLoad?.(event);
    };

    /**
     * Menangani kegagalan pemuatan gambar.
     *
     * Urutan:
     * 1. Gambar utama gagal -> coba fallbackSrc jika tersedia.
     * 2. Fallback gagal -> tampilkan fallback UI.
     * 3. Jika tidak ada fallbackSrc -> langsung tampilkan fallback UI.
     *
     * Callback pengguna tetap dijalankan pada setiap error.
     */
    const handleError: NonNullable<ImageProps['onError']> = (event) => {
        if (!useFallback && hasUsableFallback) {
            setUseFallback(true);
        } else {
            setFallbackFailed(true);
        }

        onError?.(event);
    };

    /**
     * Tampilan error.
     *
     * Pengguna dapat memberikan fallback custom.
     * Jika tidak ada, gunakan icon ImageOff dari Lucide.
     */
    if (hasFailed) {
        const fallbackContent = fallback ?? (
            <ImageOff
                aria-hidden="true"
                className="size-8 text-muted-foreground"
                strokeWidth={1.5}
            />
        );

        return (
            <div
                {...wrapperProps}
                className={joinClasses(
                    'flex items-center justify-center bg-muted',
                    className,
                    wrapperProps?.className,
                )}
                style={{
                    ...wrapperStyle,
                    ...wrapperProps?.style,
                }}
                role={wrapperProps?.role ?? 'img'}
                aria-label={
                    wrapperProps?.['aria-label'] ??
                    (alt.length > 0 ? alt : 'Gambar tidak tersedia')
                }
            >
                {fallbackContent}
            </div>
        );
    }

    /**
     * Atribut native diteruskan ke elemen <img>.
     *
     * src, alt, loading, fetchPriority, decoding, width, height,
     * srcSet, dan sizes dikelola secara eksplisit di bawah ini.
     */
    const imageElement = (
        <img
            {...imgProps}
            src={currentSrc}
            alt={alt}
            width={width}
            height={height}
            srcSet={currentSrcSet}
            sizes={currentSizes}
            loading={effectiveLoading}
            fetchPriority={effectiveFetchPriority}
            decoding={decoding}
            className={joinClasses('block w-full', imageClassName)}
            style={resolvedImageStyle}
            onLoad={handleLoad}
            onError={handleError}
        />
    );

    return (
        <div
            {...wrapperProps}
            className={joinClasses(
                position,
                className,
                wrapperProps?.className,
            )}
            style={{
                ...wrapperStyle,
                ...wrapperProps?.style,
            }}
        >
            {/*
             * Gunakan <picture> hanya jika ada responsive sources.
             * Browser memilih source berdasarkan format dan media query.
             */}
            {currentSources && currentSources.length > 0 ? (
                <picture>
                    {currentSources.map((source, index) => (
                        <source
                            key={`${source.type ?? ''}-${source.media ?? ''}-${index}`}
                            srcSet={source.srcSet}
                            type={source.type}
                            media={source.media}
                            sizes={source.sizes}
                        />
                    ))}

                    {imageElement}
                </picture>
            ) : (
                imageElement
            )}

            {/*
             * Placeholder opsional selama gambar belum selesai dimuat.
             *
             * Pointer events dinonaktifkan agar placeholder tidak
             * menghalangi interaksi pengguna dengan elemen di bawahnya.
             */}
            {!isLoaded && placeholder !== undefined && (
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 flex items-center justify-center"
                >
                    {placeholder}
                </div>
            )}
        </div>
    );
}

/**
 * Komponen Image reusable untuk seluruh aplikasi.
 *
 * Key internal dibuat dari konfigurasi sumber gambar. Ketika URL,
 * srcSet, sizes, fallbackSrc, atau sources berubah, state loading dan
 * error akan direset melalui remount ImageInstance.
 *
 * Ini mencegah gambar baru mewarisi status error dari gambar sebelumnya.
 *
 * @example
 * // Gambar konten biasa
 * <Image
 *     src="/images/parish.jpg"
 *     alt="Gereja Paroki HSPMTB Putussibau"
 *     width={1200}
 *     height={800}
 * />
 *
 * @example
 * // Gambar prioritas tinggi untuk hero
 * <Image
 *     src="/images/parish-hero.webp"
 *     alt="Gereja Paroki HSPMTB Putussibau"
 *     width={1920}
 *     height={1080}
 *     priority
 *     fit="cover"
 * />
 *
 * @example
 * // Gambar dengan rasio aspek dan fallback
 * <Image
 *     src="/storage/gallery/event.jpg"
 *     alt="Perayaan Ekaristi di gereja"
 *     aspectRatio="4 / 3"
 *     fallbackSrc="/images/image-fallback.webp"
 *     className="overflow-hidden rounded-xl"
 * />
 */
export function Image(props: ImageProps) {
    const { src, srcSet, sizes, fallbackSrc, sources } = props;

    /**
     * Key harus berubah jika konfigurasi sumber gambar berubah.
     *
     * JSON.stringify dipilih karena sources merupakan array konfigurasi
     * sederhana yang terdiri dari string dan nilai opsional.
     */
    const sourceKey = JSON.stringify({
        src,
        srcSet,
        sizes,
        fallbackSrc,
        sources,
    });

    return <ImageInstance key={sourceKey} {...props} />;
}

export default Image;
