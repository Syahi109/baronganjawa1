const dropzones = document.querySelectorAll("[data-dropzone]");

dropzones.forEach((zone) => {
  const input = zone.querySelector("input[type='file']");
  if (!input) return;

  zone.addEventListener("click", () => input.click());

  zone.addEventListener("dragover", (event) => {
    event.preventDefault();
    zone.classList.add("is-dragover");
  });

  zone.addEventListener("dragleave", () => {
    zone.classList.remove("is-dragover");
  });

  zone.addEventListener("drop", (event) => {
    event.preventDefault();
    zone.classList.remove("is-dragover");
    if (event.dataTransfer && event.dataTransfer.files.length > 0) {
      input.files = event.dataTransfer.files;
    }
  });
});

const adminOpen = document.querySelector("[data-admin-open]");
const adminModal = document.querySelector("#admin-modal");
const adminClose = document.querySelector("[data-admin-close]");

if (adminOpen && adminModal) {
  adminOpen.addEventListener("click", () => adminModal.classList.add("open"));
}
if (adminClose && adminModal) {
  adminClose.addEventListener("click", () => adminModal.classList.remove("open"));
}
if (adminModal) {
  adminModal.addEventListener("click", (event) => {
    if (event.target === adminModal) {
      adminModal.classList.remove("open");
    }
  });
}

const toast = document.querySelector("[data-toast]");
if (toast) {
  setTimeout(() => {
    toast.remove();
  }, 3000);
}

const translations = {
  id: {
    nav_home: "Beranda",
    nav_about: "Tentang",
    nav_story: "Alur Cerita",
    nav_chars: "Tokoh",
    nav_gallery: "Galeri",
    nav_schedule: "Jadwal",
    nav_forum: "Forum",
    nav_kkn: "KKN & Tim",
    hero_tag: "Seni Tradisi Desa Karangsari",
    hero_title: "Barongan Karangsari: Warisan Budaya yang Menghidupkan Desa",
    hero_lead:
      "Barongan Karangsari merupakan seni pertunjukan rakyat yang memadukan musik gamelan, tari, dan narasi pertarungan antara kebaikan dan kejahatan. Kesenian ini menjadi identitas desa, penguat kebersamaan, serta media edukasi budaya bagi generasi muda.",
    hero_btn_about: "Tentang Barongan",
    hero_btn_gallery: "Galeri",
    hero_btn_schedule: "Jadwal",
    hero_btn_forum: "Ulasan",
    about_title: "Tentang Barongan Karangsari",
    about_hist_title: "Sejarah Singkat",
    about_hist_text:
      "Barongan di Karangsari berakar dari tradisi gotong royong dan perayaan desa. Sejak puluhan tahun lalu, pertunjukan ini hadir pada berbagai acara penting sebagai wujud syukur dan doa bersama.",
    about_mean_title: "Makna bagi Masyarakat",
    about_mean_text:
      "Barongan menjadi ruang pertemuan lintas generasi, sarana pewarisan nilai, serta media untuk menumbuhkan kecintaan terhadap budaya lokal.",
    about_value_title: "Nilai Budaya & Filosofi",
    about_value_text:
      "Kisahnya menegaskan pertarungan kebaikan dan kejahatan, keberanian menghadapi tantangan, serta harmoni sebagai tujuan akhir. Kemenangan bukan sekadar unggul, melainkan menjaga keseimbangan.",
    about_role_title: "Peran dalam Acara Desa",
    about_role_text:
      "Barongan tampil pada sedekah bumi, hajatan, dan peringatan penting desa. Kesenian ini menjadi penanda momen sakral sekaligus hiburan yang meriah.",
    story_title: "Alur Cerita Pertunjukan Barongan",
    story_p1:
      "Pertunjukan dibuka dengan tabuhan gamelan yang membangun suasana hangat, sakral, dan penuh antisipasi. Penonton perlahan larut dalam ritme yang mengiringi gerak para penari.",
    story_p2:
      "Barongan kemudian tampil sebagai tokoh utama, disusul hadirnya tokoh jahat yang menantang tatanan. Konflik berkembang menjadi pertarungan dramatis yang menguji keberanian, kekuatan, dan kesetiaan pada nilai-nilai kebaikan.",
    story_p3:
      "Pada akhirnya Barongan meraih kemenangan, menegaskan pesan bahwa kebajikan menuntun harmoni. Penutup diiringi ritual singkat sebagai simbol doa keselamatan bagi masyarakat.",
    chars_title: "Tokoh & Karakter",
    chars_main_title: "Barongan (Tokoh Utama)",
    chars_main_text:
      "Melambangkan keberanian, pelindung, dan penjaga keseimbangan. Geraknya kuat, tegas, dan penuh wibawa sehingga menjadi pusat perhatian sepanjang pertunjukan.",
    chars_evil_title: "Tokoh Jahat (Rakshasa)",
    chars_evil_text:
      "Mewakili sifat angkara dan kekacauan. Kehadirannya memicu konflik sebagai titik uji bagi Barongan.",
    chars_dancer_title: "Penari & Perannya",
    chars_dancer_text:
      "Penari membentuk dinamika cerita, menjaga ritme, dan menghubungkan tiap babak. Terdapat penari inti, pengiring, serta penabuh gamelan yang menghidupkan pertunjukan.",
    chars_mask_title: "Topeng & Kostum",
    chars_mask_text:
      "Warna merah sering melambangkan keberanian, hitam keteguhan, dan emas kemuliaan. Bentuk topeng yang ekspresif mempertegas karakter baik dan jahat.",
    gallery_title: "Galeri Foto & Video",
    gallery_photo1_label: "Foto Pertunjukan",
    gallery_photo2_label: "Foto Persiapan",
    gallery_photo3_label: "Foto Suasana",
    gallery_video_label: "Video Singkat",
    gallery_video_btn: "Buka Video",
    schedule_title: "Jadwal & Informasi Pertunjukan",
    schedule_subtitle: "Jadwal Pertunjukan",
    schedule_types_title: "Jenis Kegiatan",
    schedule_types_text:
      "Barongan Karangsari dapat diundang untuk sedekah bumi, hajatan, peringatan hari besar, festival budaya, serta kegiatan desa lainnya.",
    schedule_contact_title: "Kontak Narahubung",
    schedule_contact_text: "Hubungi perwakilan atau grup untuk permohonan pertunjukan:",
    forum_title: "Forum / Ulasan Pengunjung",
    forum_name_label: "Nama (dapat anonim)",
    forum_impression_label: "Kesan setelah menonton pertunjukan",
    forum_hope_label: "Harapan untuk Barongan Karangsari ke depan",
    forum_captcha_label: "Verifikasi",
    forum_submit_btn: "Kirim Komentar",
    forum_hint: "Komentar akan tampil di daftar setelah berhasil disimpan.",
    forum_list_title: "Daftar Komentar",
    forum_empty_title: "Belum ada komentar",
    forum_empty_text: "Jadilah yang pertama berbagi kesan dan harapan.",
    forum_empty_note: "Terima kasih atas partisipasi Anda.",
    forum_hope_prefix: "Harapan",
    forum_date_prefix: "Tanggal",
    kkn_title: "Tentang KKN & Tim"
  },
  en: {
    nav_home: "Home",
    nav_about: "About",
    nav_story: "Storyline",
    nav_chars: "Characters",
    nav_gallery: "Gallery",
    nav_schedule: "Schedule",
    nav_forum: "Forum",
    nav_kkn: "KKN & Team",
    hero_tag: "Karangsari Traditional Arts",
    hero_title: "Barongan Karangsari: A Cultural Legacy That Animates the Village",
    hero_lead:
      "Barongan Karangsari is a folk performance blending gamelan music, dance, and a narrative of the struggle between good and evil. It serves as village identity, strengthens togetherness, and educates younger generations about local culture.",
    hero_btn_about: "About Barongan",
    hero_btn_gallery: "Gallery",
    hero_btn_schedule: "Schedule",
    hero_btn_forum: "Reviews",
    about_title: "About Barongan Karangsari",
    about_hist_title: "Brief History",
    about_hist_text:
      "Barongan in Karangsari is rooted in mutual cooperation and village celebrations. For decades, it has appeared at important events as a form of gratitude and shared prayer.",
    about_mean_title: "Meaning for the Community",
    about_mean_text:
      "Barongan brings generations together, passes on values, and nurtures love for local culture.",
    about_value_title: "Cultural Values & Philosophy",
    about_value_text:
      "Its story highlights the struggle between good and evil, courage in facing challenges, and harmony as the final goal. Victory is not merely winning, but preserving balance.",
    about_role_title: "Role in Village Events",
    about_role_text:
      "Barongan appears at sedekah bumi, celebrations, and important village events. It marks sacred moments while providing lively entertainment.",
    story_title: "Barongan Performance Storyline",
    story_p1:
      "The performance opens with gamelan rhythms that build a warm, sacred, and anticipatory atmosphere. The audience is gradually drawn into the movement and rhythm of the dancers.",
    story_p2:
      "Barongan then appears as the main character, followed by the evil figure who challenges the order. The conflict grows into a dramatic battle that tests courage, strength, and devotion to goodness.",
    story_p3:
      "In the end, Barongan prevails, affirming that virtue leads to harmony. The closing is accompanied by a brief ritual as a symbol of communal safety prayers.",
    chars_title: "Figures & Characters",
    chars_main_title: "Barongan (Main Figure)",
    chars_main_text:
      "Symbolizes courage, protection, and balance. Its movements are strong, firm, and commanding, becoming the focal point throughout the performance.",
    chars_evil_title: "Evil Figure (Rakshasa)",
    chars_evil_text:
      "Represents chaos and anger. Its presence triggers conflict as a test for Barongan.",
    chars_dancer_title: "Dancers & Roles",
    chars_dancer_text:
      "Dancers shape the storyline, keep the rhythm, and connect each scene. There are main dancers, supporting dancers, and gamelan players who bring the performance to life.",
    chars_mask_title: "Masks & Costumes",
    chars_mask_text:
      "Red often symbolizes courage, black steadfastness, and gold nobility. Expressive mask shapes emphasize good and evil characters.",
    gallery_title: "Photo & Video Gallery",
    gallery_photo1_label: "Performance Photo",
    gallery_photo2_label: "Preparation Photo",
    gallery_photo3_label: "Atmosphere Photo",
    gallery_video_label: "Short Video",
    gallery_video_btn: "Open Video",
    schedule_title: "Schedule & Performance Info",
    schedule_subtitle: "Performance Schedule",
    schedule_types_title: "Event Types",
    schedule_types_text:
      "Barongan Karangsari can be invited for sedekah bumi, celebrations, national days, cultural festivals, and other village events.",
    schedule_contact_title: "Contact Person",
    schedule_contact_text: "Contact a representative or group for performance requests:",
    forum_title: "Visitor Forum / Reviews",
    forum_name_label: "Name (optional)",
    forum_impression_label: "Impression after watching the performance",
    forum_hope_label: "Hopes for Barongan Karangsari",
    forum_captcha_label: "Verification",
    forum_submit_btn: "Submit Comment",
    forum_hint: "Your comment will appear after it is saved.",
    forum_list_title: "Comment List",
    forum_empty_title: "No comments yet",
    forum_empty_text: "Be the first to share your impressions and hopes.",
    forum_empty_note: "Thank you for your participation.",
    forum_hope_prefix: "Hope",
    forum_date_prefix: "Date",
    kkn_title: "About KKN & Team"
  },
  ja: {
    nav_home: "ホーム",
    nav_about: "紹介",
    nav_story: "物語",
    nav_chars: "登場人物",
    nav_gallery: "ギャラリー",
    nav_schedule: "日程",
    nav_forum: "フォーラム",
    nav_kkn: "KKNとチーム",
    hero_tag: "カランサリ村の伝統芸能",
    hero_title: "バロンガン・カランサリ：村を彩る文化遺産",
    hero_lead:
      "バロンガン・カランサリはガムラン音楽、舞踊、善と悪の物語を融合した民俗芸能です。村のアイデンティティとなり、共同体の結束を強め、若い世代への文化教育の場となります。",
    hero_btn_about: "バロンガンについて",
    hero_btn_gallery: "ギャラリー",
    hero_btn_schedule: "日程",
    hero_btn_forum: "レビュー",
    about_title: "バロンガン・カランサリについて",
    about_hist_title: "簡単な歴史",
    about_hist_text:
      "カランサリのバロンガンは相互扶助と村の祝い行事に根ざしています。何十年も重要な行事で感謝と祈りの表現として演じられてきました。",
    about_mean_title: "地域にとっての意味",
    about_mean_text:
      "バロンガンは世代を超えた交流の場であり、価値観の継承と地域文化への愛着を育む媒体です。",
    about_value_title: "文化的価値と哲学",
    about_value_text:
      "物語は善と悪の戦い、困難に立ち向かう勇気、そして調和を最終目標として強調します。勝利とは単に勝つことではなく、均衡を保つことです。",
    about_role_title: "村の行事での役割",
    about_role_text:
      "バロンガンはセデカブミ、祝い事、重要な村の行事で披露されます。神聖な瞬間を示すと同時に、にぎやかな娯楽でもあります。",
    story_title: "バロンガン公演の流れ",
    story_p1:
      "公演はガムランの演奏で始まり、温かく神聖で期待感のある雰囲気を作ります。観客は次第に踊りのリズムに引き込まれます。",
    story_p2:
      "主役のバロンガンが登場し、続いて秩序に挑む悪役が現れます。対立は勇気と力、善への忠誠を試す劇的な戦いへと発展します。",
    story_p3:
      "最終的にバロンガンが勝利し、徳が調和へ導くことを示します。締めくくりは共同体の安全を祈る短い儀式で行われます。",
    chars_title: "登場人物と役割",
    chars_main_title: "バロンガン（主役）",
    chars_main_text:
      "勇気、守護、均衡を象徴します。力強く威厳ある動きで、公演の中心となります。",
    chars_evil_title: "悪役（ラクシャサ）",
    chars_evil_text:
      "混沌と怒りを象徴します。その登場が対立を生み、バロンガンの試練となります。",
    chars_dancer_title: "踊り手と役割",
    chars_dancer_text:
      "踊り手は物語を形作り、リズムを保ち、場面をつなぎます。主役、助演、ガムラン奏者が公演を生き生きとさせます。",
    chars_mask_title: "仮面と衣装",
    chars_mask_text:
      "赤は勇気、黒は堅固さ、金は高貴さを表します。表情豊かな仮面は善悪の性格を強調します。",
    gallery_title: "写真と動画ギャラリー",
    gallery_photo1_label: "公演写真",
    gallery_photo2_label: "準備写真",
    gallery_photo3_label: "雰囲気写真",
    gallery_video_label: "短い動画",
    gallery_video_btn: "動画を見る",
    schedule_title: "日程と公演情報",
    schedule_subtitle: "公演日程",
    schedule_types_title: "開催行事",
    schedule_types_text:
      "バロンガン・カランサリはセデカブミ、祝い事、国の記念日、文化祭、その他の村の行事に招待できます。",
    schedule_contact_title: "連絡先",
    schedule_contact_text: "公演依頼は代表者またはグループへご連絡ください。",
    forum_title: "来場者フォーラム／感想",
    forum_name_label: "名前（任意）",
    forum_impression_label: "公演を見た感想",
    forum_hope_label: "今後の希望",
    forum_captcha_label: "確認",
    forum_submit_btn: "送信",
    forum_hint: "コメントは保存後に表示されます。",
    forum_list_title: "コメント一覧",
    forum_empty_title: "まだコメントがありません",
    forum_empty_text: "最初に感想と希望を共有してください。",
    forum_empty_note: "ご参加ありがとうございます。",
    forum_hope_prefix: "希望",
    forum_date_prefix: "日付",
    kkn_title: "KKNとチームについて"
  }
};

function applyTranslations(lang) {
  const dict = translations[lang] || translations.id;
  const nodes = document.querySelectorAll("[data-i18n]");
  nodes.forEach((el) => {
    const key = el.getAttribute("data-i18n");
    if (dict[key]) {
      el.textContent = dict[key];
    }
  });
}

const langSelect = document.querySelector("#lang-switch");
if (langSelect) {
  const saved = localStorage.getItem("lang") || "id";
  langSelect.value = saved;
  applyTranslations(saved);
  langSelect.addEventListener("change", () => {
    const lang = langSelect.value;
    localStorage.setItem("lang", lang);
    applyTranslations(lang);
  });
}
