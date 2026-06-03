<p>Xin chào {{ $user->full_name ?? 'Quý khách' }},</p>
<p>Tin đăng <strong>{{ $data['listing_title'] ?? 'của bạn' }}</strong> đã được nâng cấp thành công lên gói <strong>{{ $data['package_name'] ?? 'mới' }}</strong>.</p>
<p>Thời hạn gói tin: {{ $data['duration_days'] ?? 0 }} ngày.</p>
@if(!empty($data['expires_at']))
<p>Ngày hết hạn mới: {{ \Carbon\Carbon::parse($data['expires_at'])->format('d/m/Y H:i') }}.</p>
@endif
<p>Bạn có thể truy cập trang cá nhân để theo dõi trạng thái gói tin.</p>
