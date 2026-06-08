<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shipment> $shipmentsAsPenerima
 * @property-read int|null $shipments_as_penerima_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shipment> $shipmentsAsPengirim
 * @property-read int|null $shipments_as_pengirim_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer search($keyword)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $invoice_no
 * @property string|null $billed_to
 * @property int|null $manifest_id
 * @property string $no_invoice
 * @property string $tanggal
 * @property string|null $customer
 * @property string|null $catatan
 * @property numeric $total
 * @property string $status
 * @property string|null $payment_proof_path
 * @property string|null $wa_sent_to
 * @property \Illuminate\Support\Carbon|null $wa_sent_at
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Manifest|null $manifest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereBilledTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereManifestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNoInvoice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentProofPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereWaSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereWaSentTo($value)
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $invoice_id
 * @property int $shipment_id
 * @property string $no_nota
 * @property string|null $penerima
 * @property string|null $tujuan
 * @property numeric $nilai
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Invoice $invoice
 * @property-read \App\Models\Shipment $shipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereNilai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereNoNota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem wherePenerima($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTujuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUpdatedAt($value)
 */
	class InvoiceItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $no_manifest
 * @property int $manifest_ke
 * @property string|null $sopir
 * @property string|null $nopol
 * @property string $tanggal_muat
 * @property string|null $nama_kapal
 * @property string|null $keberangkatan
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManifestItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereKeberangkatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereManifestKe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereNamaKapal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereNoManifest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereNopol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereSopir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereTanggalMuat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manifest whereUpdatedAt($value)
 */
	class Manifest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Manifest|null $manifest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestBiayaOps newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestBiayaOps newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestBiayaOps query()
 */
	class ManifestBiayaOps extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $manifest_id
 * @property string|null $kode
 * @property int $koli
 * @property string $jenis_barang
 * @property string|null $pengirim
 * @property numeric $kg
 * @property string|null $penerima
 * @property string|null $tipe
 * @property string|null $tujuan
 * @property numeric $harga
 * @property string|null $keterangan
 * @property int|null $shipment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Manifest $manifest
 * @property-read \App\Models\Shipment|null $shipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereHarga($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereJenisBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereKoli($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereManifestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem wherePenerima($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem wherePengirim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereTipe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereTujuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManifestItem whereUpdatedAt($value)
 */
	class ManifestItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $no_nota
 * @property string $tanggal
 * @property string $nama_pengirim
 * @property string|null $telp_pengirim
 * @property string $nama_penerima
 * @property string $telp_penerima
 * @property string $alamat_penerima
 * @property string $tujuan
 * @property numeric $harga_total
 * @property string|null $keterangan
 * @property string|null $wa_penerima_sent_at
 * @property string|null $wa_pengirim_sent_at
 * @property string $status_pengiriman
 * @property string $status_pembayaran
 * @property string $tipe_bayar
 * @property string|null $bukti_bayar_path
 * @property string|null $paid_at
 * @property string|null $bukti_bayar
 * @property int|null $manifest_id
 * @property string|null $manifested_at
 * @property int|null $admin_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer|null $customerPenerima
 * @property-read \App\Models\Customer|null $customerPengirim
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $invoiceItems
 * @property-read int|null $invoice_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShipmentItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShipmentLog> $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Manifest|null $manifest
 * @property-read \App\Models\ManifestItem|null $manifestItem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManifestItem> $manifestItems
 * @property-read int|null $manifest_items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereAlamatPenerima($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereBuktiBayar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereBuktiBayarPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereHargaTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereManifestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereManifestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereNamaPenerima($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereNamaPengirim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereNoNota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereStatusPembayaran($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereStatusPengiriman($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTelpPenerima($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTelpPengirim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTipeBayar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTujuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereWaPenerimaSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereWaPengirimSentAt($value)
 */
	class Shipment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shipment_id
 * @property string $nama_barang
 * @property int $koli
 * @property string $satuan_tarif
 * @property numeric $berat_kg
 * @property numeric $kubikasi_m3
 * @property int $harga_satuan
 * @property int $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shipment $shipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereBeratKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereHargaSatuan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereKoli($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereKubikasiM3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereNamaBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereSatuanTarif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentItem whereUpdatedAt($value)
 */
	class ShipmentItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shipment_id
 * @property string $action
 * @property string|null $description
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $logged_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shipment $shipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereLoggedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShipmentLog whereUpdatedAt($value)
 */
	class ShipmentLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $username
 * @property string $name
 * @property string $email
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 */
	class User extends \Eloquent {}
}

