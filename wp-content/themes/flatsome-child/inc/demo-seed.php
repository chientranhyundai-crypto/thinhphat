<?php
/**
 * Phase 11 — Seed full demo catalog/content. Safe to re-run (skips if sa_demo_seeded).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import a local image file into Media Library and mark demo.
 *
 * @param string $path Absolute path.
 * @param string $title Title.
 * @param string $alt Alt text.
 * @return int Attachment ID.
 */
function sa_demo_import_image( $path, $title, $alt = '' ) {
	if ( ! file_exists( $path ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$filename = sanitize_file_name( basename( $path ) );
	$upload   = wp_upload_bits( $filename, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype = wp_check_filetype( $upload['file'], null );
	$attach_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return 0;
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt ? $alt : $title );
	sa_mark_demo( $attach_id );
	return (int) $attach_id;
}

/**
 * Ensure attribute term exists.
 *
 * @param string $taxonomy Taxonomy.
 * @param string $name Term name.
 * @return int
 */
function sa_demo_ensure_term( $taxonomy, $name ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return 0;
	}
	$term = term_exists( $name, $taxonomy );
	if ( ! $term ) {
		$term = wp_insert_term( $name, $taxonomy );
	}
	if ( is_wp_error( $term ) ) {
		return 0;
	}
	return (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

/**
 * Create one demo product.
 *
 * @param array $data Product data.
 * @param int   $image_id Attachment ID.
 * @return int
 */
function sa_demo_create_product( $data, $image_id ) {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return 0;
	}

	$existing = wc_get_product_id_by_sku( $data['sku'] );
	if ( $existing ) {
		sa_mark_demo( $existing );
		return (int) $existing;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $data['name'] );
	$product->set_sku( $data['sku'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_short_description( $data['short'] );
	$product->set_description( $data['long'] );
	$product->set_category_ids( array( (int) $data['cat_id'] ) );
	$product->set_featured( ! empty( $data['featured'] ) );

	if ( ! empty( $data['contact_price'] ) ) {
		$product->set_regular_price( '' );
		$product->set_price( '' );
		$product->update_meta_data( '_sa_price_on_request', 'yes' );
	} else {
		$product->set_regular_price( (string) $data['price'] );
		$product->set_price( (string) $data['price'] );
		$product->update_meta_data( '_sa_price_on_request', 'no' );
	}

	$product->update_meta_data( '_sa_key_specs', $data['specs'] );
	$product->update_meta_data( '_sa_demo_data', '1' );

	if ( $image_id ) {
		$product->set_image_id( $image_id );
	}

	// Attributes.
	$attrs = array();
	$map   = array(
		'pa_thuong-hieu' => $data['brand'],
		'pa_cong-suat'   => $data['power'],
		'pa_dien-ap'     => $data['voltage'],
	);
	foreach ( $map as $tax => $val ) {
		$tid = sa_demo_ensure_term( $tax, $val );
		if ( ! $tid ) {
			continue;
		}
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
		$attribute->set_name( $tax );
		$attribute->set_options( array( $val ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attrs[] = $attribute;
		wp_set_object_terms( 0, array( $val ), $tax, true ); // ensure global term exists before save.
	}
	$product->set_attributes( $attrs );

	$id = $product->save();
	if ( $id ) {
		foreach ( $map as $tax => $val ) {
			wp_set_object_terms( $id, array( $val ), $tax );
		}
		sa_mark_demo( $id );
	}
	return (int) $id;
}

/**
 * Seed all demo data.
 *
 * @param bool $force Force re-seed after remove.
 * @return array<string,mixed>
 */
function sa_seed_demo_data( $force = false ) {
	if ( ! $force && get_option( 'sa_demo_seeded' ) ) {
		return array( 'skipped' => 1, 'reason' => 'already_seeded' );
	}

	sa_register_product_attributes();

	$asset_dir = '/tmp/sa-demo-assets';
	$imgs      = array();

	$import_map = array(
		'tower'      => array( "$asset_dir/demo-ups-tower.png", 'Demo UPS tower', 'UPS tower demo' ),
		'rack'       => array( "$asset_dir/demo-ups-rack.png", 'Demo UPS rack', 'UPS rack demo' ),
		'industrial' => array( "$asset_dir/demo-ups-industrial.png", 'Demo UPS industrial', 'UPS industrial demo' ),
		'battery'    => array( "$asset_dir/demo-battery-inverter.png", 'Demo battery inverter', 'Battery inverter demo' ),
		'factory'    => array( "$asset_dir/demo-project-factory.png", 'Demo project factory', 'Factory project demo' ),
		'datacenter' => array( "$asset_dir/demo-project-datacenter.png", 'Demo project datacenter', 'Datacenter project demo' ),
		'office'     => array( "$asset_dir/demo-project-office.png", 'Demo project office', 'Office project demo' ),
		'indproj'    => array( "$asset_dir/demo-project-industrial.png", 'Demo project industrial', 'Industrial project demo' ),
		'news'       => array( "$asset_dir/thinhphat-news-main.png", 'Demo news thumb', 'News demo' ),
		'video'      => array( "$asset_dir/thinhphat-video-cover.png", 'Demo video cover', 'Video demo' ),
		'offline'    => array( "$asset_dir/thinhphat-cat-ups-offline.png", 'Demo cat offline', 'UPS Offline' ),
		'online'     => array( "$asset_dir/thinhphat-cat-ups-online.png", 'Demo cat online', 'UPS Online' ),
		'inverter'   => array( "$asset_dir/thinhphat-cat-inverter.png", 'Demo cat inverter', 'Inverter' ),
	);

	foreach ( $import_map as $key => $row ) {
		$imgs[ $key ] = sa_demo_import_image( $row[0], $row[1], $row[2] );
	}

	$cats = array();
	foreach ( array( 'ups-offline', 'ups-online', 'ups-online-rack', 'ups-cong-nghiep', 'bien-tan', 'pin-ac-quy' ) as $slug ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		$cats[ $slug ] = $t ? (int) $t->term_id : 0;
	}

	$products_def = array(
		array(
			'name' => 'UPS Offline TP-OFF 650VA',
			'sku' => 'TP-OFF-650',
			'cat' => 'ups-offline',
			'img' => 'tower',
			'brand' => 'TP Series',
			'power' => '650VA / 360W',
			'voltage' => '220VAC',
			'specs' => 'Line-interactive · AVR · LED · Backup ~10–15 phút · 1× ổ IEC',
			'short' => 'UPS Offline gọn cho máy tính văn phòng và thiết bị IT nhẹ.',
			'long' => "<p>UPS Offline TP-OFF 650VA thuộc dòng DEMO TP Series, phù hợp máy trạm, modem/router và thiết bị văn phòng nhỏ.</p><ul><li>Công suất: 650VA / 360W</li><li>Điện áp vào/ra: 220VAC</li><li>Chế độ: Offline / Line-interactive</li><li>Ổn áp AVR hỗ trợ điện lưới dao động</li><li>Báo trạng thái LED</li><li>Thời gian lưu điện tham khảo: 10–15 phút (tải nhẹ)</li></ul><p><em>Dữ liệu minh họa (DEMO) phục vụ trình diễn website.</em></p>",
			'price' => '1890000',
			'featured' => 1,
		),
		array(
			'name' => 'UPS Offline TP-OFF 1200VA',
			'sku' => 'TP-OFF-1200',
			'cat' => 'ups-offline',
			'img' => 'offline',
			'brand' => 'TP Series',
			'power' => '1200VA / 720W',
			'voltage' => '220VAC',
			'specs' => 'Line-interactive · AVR · USB monitoring · 2× ổ output',
			'short' => 'UPS Offline 1200VA cho cụm máy tính và thiết bị mạng văn phòng.',
			'long' => "<p>Mẫu DEMO TP-OFF 1200VA mở rộng tải cho nhóm máy tính và thiết bị mạng.</p><ul><li>1200VA / 720W</li><li>220VAC</li><li>AVR ổn áp</li><li>Cổng USB giám sát (minh họa)</li><li>2 ổ output</li><li>Thiết kế tower gọn</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '2750000',
			'featured' => 1,
		),
		array(
			'name' => 'UPS Offline TP-OFF 2000VA',
			'sku' => 'TP-OFF-2000',
			'cat' => 'ups-offline',
			'img' => 'tower',
			'brand' => 'TP Series',
			'power' => '2000VA / 1200W',
			'voltage' => '220VAC',
			'specs' => 'Line-interactive · AVR rộng · Bảo vệ quá tải · LCD',
			'short' => 'UPS Offline 2000VA cho văn phòng vừa và thiết bị POS.',
			'long' => "<p>TP-OFF 2000VA (DEMO) phục vụ tải văn phòng vừa.</p><ul><li>2000VA / 1200W</li><li>220VAC</li><li>LCD trạng thái</li><li>Bảo vệ quá tải / ngắn mạch (mô tả demo)</li><li>AVR dải rộng</li><li>Form tower</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '3890000',
			'featured' => 0,
		),
		array(
			'name' => 'UPS Online TP-ON 1K LCD',
			'sku' => 'TP-ON-1K',
			'cat' => 'ups-online',
			'img' => 'online',
			'brand' => 'TP Series',
			'power' => '1000VA / 800W',
			'voltage' => '220VAC',
			'specs' => 'Online double-conversion · 0ms · LCD · PF 0.8',
			'short' => 'UPS Online 1K cho máy chủ nhỏ và thiết bị quan trọng.',
			'long' => "<p>TP-ON 1K LCD (DEMO) dùng công nghệ online double-conversion.</p><ul><li>1000VA / 800W</li><li>220VAC</li><li>Chuyển mạch 0ms (online)</li><li>Màn hình LCD</li><li>Hệ số công suất 0.8</li><li>Bảo vệ sét/surge (mô tả demo)</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '7250000',
			'featured' => 1,
		),
		array(
			'name' => 'UPS Online TP-ON 3K LCD',
			'sku' => 'TP-ON-3K',
			'cat' => 'ups-online',
			'img' => 'online',
			'brand' => 'TP Series',
			'power' => '3000VA / 2400W',
			'voltage' => '220VAC',
			'specs' => 'Online · LCD · ECO mode · PF 0.8 · USB/RS232',
			'short' => 'UPS Online 3K cho phòng server nhỏ và thiết bị y tế nhẹ.',
			'long' => "<p>TP-ON 3K LCD (DEMO) cân bằng công suất và chi phí cho phòng máy nhỏ.</p><ul><li>3000VA / 2400W</li><li>220VAC</li><li>Online double-conversion</li><li>Chế độ ECO (minh họa)</li><li>Giao tiếp USB/RS232</li><li>LCD đa thông số</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '13950000',
			'featured' => 1,
		),
		array(
			'name' => 'UPS Online TP-ON 6K LCD',
			'sku' => 'TP-ON-6K',
			'cat' => 'ups-online',
			'img' => 'tower',
			'brand' => 'TP Series',
			'power' => '6000VA / 4800W',
			'voltage' => '220VAC',
			'specs' => 'Online · PF 0.8 · Bypass · Mở rộng battery pack',
			'short' => 'UPS Online 6K cho tải quan trọng và phòng kỹ thuật.',
			'long' => "<p>TP-ON 6K LCD (DEMO) hướng tới tải quan trọng quy mô vừa.</p><ul><li>6000VA / 4800W</li><li>220VAC</li><li>Bypass bảo trì</li><li>Hỗ trợ battery pack mở rộng (mô tả)</li><li>Online double-conversion</li><li>LCD + cảnh báo âm thanh</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '28550000',
			'featured' => 1,
		),
		array(
			'name' => 'UPS Online Rack TP-RK 1K',
			'sku' => 'TP-RK-1K',
			'cat' => 'ups-online-rack',
			'img' => 'rack',
			'brand' => 'TP Series',
			'power' => '1000VA / 800W',
			'voltage' => '220VAC',
			'specs' => '1U/2U rack · Online · LCD · Hot-swap battery (demo)',
			'short' => 'UPS Online rack 1K cho tủ rack mạng và server edge.',
			'long' => "<p>TP-RK 1K (DEMO) tối ưu lắp tủ rack 19\".</p><ul><li>1000VA / 800W</li><li>220VAC</li><li>Form rackmount</li><li>Online double-conversion</li><li>LCD mặt trước</li><li>Pin có thể thay (mô tả demo)</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '8950000',
			'featured' => 1,
		),
		array(
			'name' => 'UPS Online Rack TP-RK 3K',
			'sku' => 'TP-RK-3K',
			'cat' => 'ups-online-rack',
			'img' => 'rack',
			'brand' => 'TP Series',
			'power' => '3000VA / 2400W',
			'voltage' => '220VAC',
			'specs' => 'Rack 2U · Online · PF 0.8 · SNMP slot (demo)',
			'short' => 'UPS Online rack 3K cho tủ máy chủ và phòng mạng.',
			'long' => "<p>TP-RK 3K (DEMO) cho tải rack dày đặc hơn.</p><ul><li>3000VA / 2400W</li><li>220VAC</li><li>2U rack</li><li>Khe SNMP (minh họa)</li><li>Online</li><li>Quản lý từ xa (mô tả)</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '16800000',
			'featured' => 0,
		),
		array(
			'name' => 'UPS Công nghiệp TP-IND 10K',
			'sku' => 'TP-IND-10K',
			'cat' => 'ups-cong-nghiep',
			'img' => 'industrial',
			'brand' => 'TP Series',
			'power' => '10kVA',
			'voltage' => '380VAC 3-pha',
			'specs' => '3-pha · Online · Cabinet · Parallel ready (demo)',
			'short' => 'UPS công nghiệp 10kVA cho dây chuyền và phòng điều khiển.',
			'long' => "<p>TP-IND 10K (DEMO) hướng tới môi trường công nghiệp nhẹ–vừa.</p><ul><li>10kVA</li><li>380VAC 3-pha</li><li>Online</li><li>Tủ cabinet</li><li>Sẵn sàng song song (mô tả)</li><li>Giao diện giám sát</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '',
			'contact_price' => 1,
			'featured' => 1,
		),
		array(
			'name' => 'UPS Công nghiệp TP-IND 20K',
			'sku' => 'TP-IND-20K',
			'cat' => 'ups-cong-nghiep',
			'img' => 'industrial',
			'brand' => 'TP Series',
			'power' => '20kVA',
			'voltage' => '380VAC 3-pha',
			'specs' => '3-pha · Online · High overload · Battery cabinet',
			'short' => 'UPS công nghiệp 20kVA cho nhà máy và hạ tầng kỹ thuật.',
			'long' => "<p>TP-IND 20K (DEMO) cho tải sản xuất liên tục.</p><ul><li>20kVA</li><li>380VAC 3-pha</li><li>Online</li><li>Khả năng quá tải cao (mô tả)</li><li>Tủ ắc quy tách</li><li>Bypass bảo trì</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '',
			'contact_price' => 1,
			'featured' => 1,
		),
		array(
			'name' => 'UPS Công nghiệp TP-IND 40K',
			'sku' => 'TP-IND-40K',
			'cat' => 'ups-cong-nghiep',
			'img' => 'industrial',
			'brand' => 'TP Series',
			'power' => '40kVA',
			'voltage' => '380VAC 3-pha',
			'specs' => '3-pha · Modular option · N+1 (demo) · LCD touch',
			'short' => 'UPS công nghiệp 40kVA cho khu vực tải lớn.',
			'long' => "<p>TP-IND 40K (DEMO) phục vụ demo giải pháp công suất lớn.</p><ul><li>40kVA</li><li>380VAC 3-pha</li><li>Tuỳ chọn modular (mô tả)</li><li>Cấu hình N+1 minh họa</li><li>LCD cảm ứng</li><li>Giám sát tập trung</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '',
			'contact_price' => 1,
			'featured' => 0,
		),
		array(
			'name' => 'Biến tần TP-INV 3K Hybrid',
			'sku' => 'TP-INV-3K',
			'cat' => 'bien-tan',
			'img' => 'inverter',
			'brand' => 'TP Series',
			'power' => '3000W',
			'voltage' => '48VDC / 220VAC',
			'specs' => 'Hybrid · MPPT · Pure sine · LCD',
			'short' => 'Biến tần hybrid 3K cho hệ lưu trữ nhỏ và backup hộ gia đình/doanh nghiệp nhỏ.',
			'long' => "<p>TP-INV 3K Hybrid (DEMO) kết hợp lưới và pin lưu trữ.</p><ul><li>3000W</li><li>48VDC / 220VAC</li><li>MPPT (mô tả)</li><li>Sóng sine chuẩn</li><li>LCD cấu hình</li><li>Chế độ hybrid</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '12500000',
			'featured' => 0,
		),
		array(
			'name' => 'Biến tần TP-INV 5K Hybrid',
			'sku' => 'TP-INV-5K',
			'cat' => 'bien-tan',
			'img' => 'battery',
			'brand' => 'TP Series',
			'power' => '5000W',
			'voltage' => '48VDC / 220VAC',
			'specs' => 'Hybrid · Dual MPPT · Parallel · Pure sine',
			'short' => 'Biến tần hybrid 5K cho hệ lưu trữ doanh nghiệp nhỏ.',
			'long' => "<p>TP-INV 5K Hybrid (DEMO) mở rộng công suất lưu trữ.</p><ul><li>5000W</li><li>48VDC / 220VAC</li><li>Dual MPPT (mô tả)</li><li>Song song nhiều máy (mô tả)</li><li>Pure sine</li><li>Giám sát app (minh họa)</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '18900000',
			'featured' => 0,
		),
		array(
			'name' => 'Ắc quy TP-BAT 12V 100Ah',
			'sku' => 'TP-BAT-12V100',
			'cat' => 'pin-ac-quy',
			'img' => 'battery',
			'brand' => 'TP Series',
			'power' => '100Ah',
			'voltage' => '12VDC',
			'specs' => 'AGM · Deep cycle · Bảo trì thấp · 10 năm thiết kế (demo)',
			'short' => 'Ắc quy AGM 12V 100Ah cho hệ UPS và backup.',
			'long' => "<p>TP-BAT 12V100 (DEMO) dùng minh họa bank ắc quy UPS.</p><ul><li>12V / 100Ah</li><li>AGM</li><li>Deep cycle</li><li>Bảo trì thấp</li><li>Tuổi thọ thiết kế minh họa</li><li>Tương thích UPS tower/rack</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '2450000',
			'featured' => 0,
		),
		array(
			'name' => 'Ắc quy TP-BAT 12V 200Ah',
			'sku' => 'TP-BAT-12V200',
			'cat' => 'pin-ac-quy',
			'img' => 'battery',
			'brand' => 'TP Series',
			'power' => '200Ah',
			'voltage' => '12VDC',
			'specs' => 'AGM · High capacity · Terminal M8 · Cabinet ready',
			'short' => 'Ắc quy AGM 12V 200Ah cho hệ backup thời gian dài.',
			'long' => "<p>TP-BAT 12V200 (DEMO) cho thời gian lưu điện dài hơn.</p><ul><li>12V / 200Ah</li><li>AGM</li><li>Dung lượng cao</li><li>Cọc M8</li><li>Phù hợp tủ ắc quy</li><li>Deep cycle</li></ul><p><em>Dữ liệu minh họa (DEMO).</em></p>",
			'price' => '4290000',
			'featured' => 0,
		),
	);

	$product_ids = array();
	foreach ( $products_def as $p ) {
		$img_key = $p['img'];
		$id      = sa_demo_create_product(
			array(
				'name'          => $p['name'],
				'sku'           => $p['sku'],
				'cat_id'        => $cats[ $p['cat'] ],
				'brand'         => $p['brand'],
				'power'         => $p['power'],
				'voltage'       => $p['voltage'],
				'specs'         => $p['specs'],
				'short'         => $p['short'],
				'long'          => $p['long'],
				'price'         => isset( $p['price'] ) ? $p['price'] : '',
				'contact_price' => ! empty( $p['contact_price'] ),
				'featured'      => ! empty( $p['featured'] ),
			),
			isset( $imgs[ $img_key ] ) ? $imgs[ $img_key ] : 0
		);
		if ( $id ) {
			$product_ids[] = $id;
		}
	}

	// Projects.
	$projects = array(
		array(
			'title'   => 'Giải pháp UPS cho nhà máy sản xuất DEMO',
			'tag'     => 'Nhà máy',
			'excerpt' => 'Cấp nguồn liên tục cho dây chuyền và phòng điều khiển — dữ liệu minh họa.',
			'content' => '<p>Dự án DEMO mô tả triển khai UPS công nghiệp kết hợp bank ắc quy cho khu vực sản xuất.</p><p>Giải pháp: TP-IND 20K + tủ ắc quy, giám sát trạng thái tải.</p><p><em>Nội dung minh họa (DEMO) — không gắn khách hàng thật.</em></p>',
			'img'     => 'factory',
		),
		array(
			'title'   => 'Nguồn dự phòng trung tâm dữ liệu DEMO',
			'tag'     => 'Data center',
			'excerpt' => 'UPS online rack và phân phối nguồn cho tủ server — minh họa.',
			'content' => '<p>Dự án DEMO cho phòng máy: UPS rack online, PDU và thời gian lưu điện mở rộng.</p><p>Giải pháp: TP-RK 3K + TP-ON 6K (minh họa kiến trúc).</p><p><em>Nội dung minh họa (DEMO).</em></p>',
			'img'     => 'datacenter',
		),
		array(
			'title'   => 'Backup văn phòng doanh nghiệp DEMO',
			'tag'     => 'Văn phòng',
			'excerpt' => 'Bảo vệ máy trạm, tổng đài và thiết bị mạng văn phòng.',
			'content' => '<p>Dự án DEMO văn phòng: UPS Offline/Online cho cụm IT và phòng họp.</p><p>Giải pháp: TP-OFF 1200 + TP-ON 1K.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
			'img'     => 'office',
		),
		array(
			'title'   => 'Hệ thống nguồn khu kỹ thuật công nghiệp DEMO',
			'tag'     => 'Công nghiệp',
			'excerpt' => 'Ổn định nguồn cho tủ điều khiển và thiết bị đo lường.',
			'content' => '<p>Dự án DEMO khu kỹ thuật: UPS 3-pha và giám sát tập trung.</p><p>Giải pháp: TP-IND 10K.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
			'img'     => 'indproj',
		),
		array(
			'title'   => 'Mở rộng thời gian lưu điện kho DEMO',
			'tag'     => 'Lưu trữ',
			'excerpt' => 'Bổ sung bank ắc quy và biến tần hybrid cho khu vực phụ trợ.',
			'content' => '<p>Dự án DEMO kết hợp UPS và lưu trữ: TP-INV 5K + TP-BAT 12V200.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
			'img'     => 'battery',
		),
	);

	$project_ids = array();
	foreach ( $projects as $pr ) {
		$pid = wp_insert_post(
			array(
				'post_type'    => 'sa_project',
				'post_status'  => 'publish',
				'post_title'   => $pr['title'],
				'post_excerpt' => $pr['excerpt'],
				'post_content' => $pr['content'],
			),
			true
		);
		if ( is_wp_error( $pid ) ) {
			continue;
		}
		update_post_meta( $pid, '_sa_project_tag', $pr['tag'] );
		sa_mark_demo( $pid );
		if ( ! empty( $imgs[ $pr['img'] ] ) ) {
			set_post_thumbnail( $pid, $imgs[ $pr['img'] ] );
		}
		$project_ids[] = $pid;
	}

	// News posts.
	$posts_def = array(
		array(
			'title'   => 'Cách chọn UPS phù hợp cho doanh nghiệp',
			'excerpt' => 'Các tiêu chí công suất, topology và thời gian lưu điện khi chọn UPS.',
			'content' => '<p>Bài DEMO hướng dẫn khung chọn UPS: xác định tải, hệ số an toàn, Online/Offline, và môi trường lắp đặt.</p><p>Không thay thế tư vấn kỹ thuật hiện trường.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
		array(
			'title'   => 'UPS Online và Offline khác nhau như thế nào?',
			'excerpt' => 'So sánh topology, thời gian chuyển mạch và ứng dụng điển hình.',
			'content' => '<p>Bài DEMO phân biệt Offline/Line-interactive và Online double-conversion, kèm ví dụ ứng dụng văn phòng và phòng server.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
		array(
			'title'   => 'Công thức ước tính công suất UPS',
			'excerpt' => 'Từ tổng tải Watt/VA đến chọn mức công suất có dự phòng.',
			'content' => '<p>Bài DEMO nêu cách cộng tải, đổi W↔VA theo PF, và thêm biên độ 20–30%.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
		array(
			'title'   => 'Bảo trì UPS định kỳ: checklist thực tế',
			'excerpt' => 'Pin, quạt, môi trường và nhật ký cảnh báo cần theo dõi.',
			'content' => '<p>Bài DEMO liệt kê checklist bảo trì: vệ sinh, kiểm tra ắc quy, test bypass, cập nhật firmware (khi có).</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
		array(
			'title'   => 'Pin & ắc quy cho hệ UPS: AGM và lưu ý vận hành',
			'excerpt' => 'Nhiệt độ, chu kỳ xả và dấu hiệu chai pin thường gặp.',
			'content' => '<p>Bài DEMO về bank ắc quy AGM, nhiệt độ phòng pin và thay thế theo nhóm.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
		array(
			'title'   => 'Biến tần hybrid trong giải pháp nguồn doanh nghiệp nhỏ',
			'excerpt' => 'Khi nào nên kết hợp biến tần, pin lưu trữ và UPS.',
			'content' => '<p>Bài DEMO mô tả vai trò biến tần hybrid trong backup phụ trợ và tối ưu chi phí năng lượng.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
		array(
			'title'   => 'Bố trí tủ rack và UPS Online Rack an toàn',
			'excerpt' => 'Thông gió, tải trọng và phân phối PDU cơ bản.',
			'content' => '<p>Bài DEMO lưu ý lắp UPS rack: luồng gió, khoảng cách, cân tải pha và giám sát từ xa.</p><p><em>Nội dung minh họa (DEMO).</em></p>',
		),
	);

	$post_ids = array();
	foreach ( $posts_def as $i => $pd ) {
		$pid = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $pd['title'],
				'post_excerpt' => $pd['excerpt'],
				'post_content' => $pd['content'],
			),
			true
		);
		if ( is_wp_error( $pid ) ) {
			continue;
		}
		sa_mark_demo( $pid );
		$thumb = ! empty( $imgs['news'] ) ? $imgs['news'] : ( ! empty( $imgs['office'] ) ? $imgs['office'] : 0 );
		if ( $thumb ) {
			set_post_thumbnail( $pid, $thumb );
		}
		$post_ids[] = $pid;
	}

	// Videos option.
	$video_cover = ! empty( $imgs['video'] ) ? wp_get_attachment_image_url( $imgs['video'], 'large' ) : '';
	$videos      = array(
		array(
			'title'     => 'Giới thiệu giải pháp UPS (DEMO)',
			'image_url' => $video_cover,
			'image_id'  => isset( $imgs['video'] ) ? $imgs['video'] : 0,
		),
		array(
			'title'     => 'Hướng dẫn lựa chọn UPS (DEMO)',
			'image_url' => $video_cover,
			'image_id'  => isset( $imgs['video'] ) ? $imgs['video'] : 0,
		),
		array(
			'title'     => 'Giải pháp nguồn điện doanh nghiệp (DEMO)',
			'image_url' => $video_cover,
			'image_id'  => isset( $imgs['video'] ) ? $imgs['video'] : 0,
		),
	);
	update_option( 'sa_demo_videos', $videos, false );

	// Partners monograms (neutral, fictional).
	$partners = array(
		array( 'mono' => 'NA', 'name' => 'Nova Axis (DEMO)' ),
		array( 'mono' => 'HK', 'name' => 'Harbor Kinetic (DEMO)' ),
		array( 'mono' => 'QL', 'name' => 'Quanta Line (DEMO)' ),
		array( 'mono' => 'OR', 'name' => 'Orbit Rail (DEMO)' ),
		array( 'mono' => 'VS', 'name' => 'Volt Square (DEMO)' ),
		array( 'mono' => 'CM', 'name' => 'Cedar Mill (DEMO)' ),
	);
	update_option( 'sa_demo_partners', $partners, false );

	// Numbers demo — clearly marked.
	$numbers = array(
		array( 'value' => '120+', 'label' => 'Dự án minh họa (DEMO)' ),
		array( 'value' => '80+', 'label' => 'Khách hàng mẫu (DEMO)' ),
		array( 'value' => '15+', 'label' => 'Dòng sản phẩm DEMO' ),
		array( 'value' => '24/7', 'label' => 'Khung hỗ trợ DEMO' ),
	);
	update_option( 'sa_demo_numbers', $numbers, false );

	update_option( 'sa_demo_seeded', 1, false );
	flush_rewrite_rules( false );

	return array(
		'products'    => count( $product_ids ),
		'product_ids' => $product_ids,
		'projects'    => count( $project_ids ),
		'posts'       => count( $post_ids ),
		'images'      => array_filter( $imgs ),
		'videos'      => count( $videos ),
		'partners'    => count( $partners ),
		'numbers'     => count( $numbers ),
		'featured'    => count(
			array_filter(
				$products_def,
				function ( $p ) {
					return ! empty( $p['featured'] );
				}
			)
		),
	);
}
