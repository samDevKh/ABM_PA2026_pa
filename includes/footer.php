<?php
/**
 * footer.php
 * เว็บไซต์โรงเรียนอนุบาลบ้านม่วง
 * สังกัดสำนักงานเขตพื้นที่การศึกษาประถมศึกษาสกลนคร เขต 3
 */
?>

<footer class="site-footer">

    <!-- Footer Main -->
    <div class="footer-main">
        <div class="container">

            <div class="footer-grid">

                <!-- School Information -->
                <div class="footer-column school-info">

                    <div class="footer-brand">
                        <div class="school-logo">
                            <img
                                src="assets/images/abmlogo.png"
                                alt="ตราโรงเรียนอนุบาลบ้านม่วง"
                                loading="lazy"
                            >
                        </div>

                        <div>
                            <h3>โรงเรียนอนุบาลบ้านม่วง</h3>
                            <p>ANUBAN BANMUANG SCHOOL</p>
                        </div>
                    </div>

                    <p class="footer-description">
                        สถานศึกษาที่มุ่งพัฒนาผู้เรียนให้มีความรู้
                        ทักษะ คุณธรรม และสมรรถนะที่จำเป็น
                        พร้อมก้าวสู่อนาคตอย่างมีคุณภาพ
                    </p>

                </div>
                



                <!-- Quick Links -->
                <div class="footer-column">

                    <h4>เมนูเว็บไซต์</h4>

                    <ul class="footer-links">
                        <li>
                            <a href="index.php">
                                <span>›</span> หน้าแรก
                            </a>
                        </li>

                        <li>
                            <a href="about.php">
                                <span>›</span> เกี่ยวกับโรงเรียน
                            </a>
                        </li>

                        <li>
                            <a href="news.php">
                                <span>›</span> ข่าวประชาสัมพันธ์
                            </a>
                        </li>

                        <li>
                            <a href="activities.php">
                                <span>›</span> กิจกรรม
                            </a>
                        </li>

                        <li>
                            <a href="downloads.php">
                                <span>›</span> ดาวน์โหลดเอกสาร
                            </a>
                        </li>

                        <li>
                            <a href="contact.php">
                                <span>›</span> ติดต่อโรงเรียน
                            </a>
                        </li>
                    </ul>

                </div>


                <!-- Useful Links -->
                <div class="footer-column">

                    <h4>หน่วยงานที่เกี่ยวข้อง</h4>

                    <ul class="footer-links">

                        <li>
                            <a
                                href="https://www.sakonarea3.go.th/"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>›</span>
                                สพป.สกลนคร เขต 3
                            </a>
                        </li>

                        <li>
                            <a
                                href="https://www.obec.go.th/"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>›</span>
                                สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน
                            </a>
                        </li>

                        <li>
                            <a
                                href="https://www.moe.go.th/"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>›</span>
                                กระทรวงศึกษาธิการ
                            </a>
                        </li>

                    </ul>

                </div>



                <!-- Contact -->
                <div class="footer-column">

                    <h4>ติดต่อโรงเรียน</h4>

                    <ul class="contact-list">

                        <li>
                            <span class="contact-icon">📍</span>
                            <span>
                                โรงเรียนอนุบาลบ้านม่วง<br>
                                จังหวัดสกลนคร
                            </span>
                        </li>

                        <li>
                            <span class="contact-icon">☎</span>
                            <span>
                                โทรศัพท์ : -
                            </span>
                        </li>

                        <li>
                            <span class="contact-icon">✉</span>
                            <span>
                                อีเมล : -
                            </span>
                        </li>

                    </ul>

                </div>

            </div>

        </div>
    </div>


    <!-- Footer Bottom -->
    <div class="footer-bottom">

        <div class="container">

            <div class="footer-bottom-content">

                <div class="copyright">
                    © <?php echo date('Y'); ?>
                    โรงเรียนอนุบาลบ้านม่วง
                    สงวนลิขสิทธิ์
                </div>

                <div class="footer-agency">
                    สังกัดสำนักงานเขตพื้นที่การศึกษาประถมศึกษาสกลนคร เขต 3
                </div>

            </div>

        </div>

    </div>

</footer>


<!-- Back To Top -->
<button
    type="button"
    id="backToTop"
    class="back-to-top"
    aria-label="กลับขึ้นด้านบน"
>
    ↑
</button>


<style>

/* ========================================
   FOOTER
======================================== */

.site-footer {
    color: #FFFFFF;
    background: #FFFFFF;
    margin-top: 60px;
}

/* Container */

.site-footer .container {
    width: min(800px, calc(100% - 40px));
    margin: 0 auto;
}


/* Main */

.footer-main {
    padding: 55px 0 45px;
}


/* Grid */

.footer-grid {
    display: grid;
    grid-template-columns:
        1.5fr
        1fr
        1.3fr
        1.2fr;

    gap: 45px;
}


/* Brand */

.footer-brand {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.school-logo {
    width: 68px;
    height: 68px;
    background: #FBEDFF;
    border-radius: 50%;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.school-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
}

.footer-brand h3 {
    margin: 0 0 4px;
    color: #350F73;
    font-size: 20px;
    font-weight: 700;
}

.footer-brand p {
    margin: 0;
    color: #350F73;
    font-size: 12px;
    letter-spacing: 0.5px;
}


/* Description */

.footer-description {
    max-width: 380px;
    margin: 0;
    color: #350F73;
    font-size: 14px;
    line-height: 1.8;
}


/* Heading */

.footer-column h4 {
    position: relative;
    margin: 0 0 20px;
    padding-bottom: 10px;

    color: #350F73;
    font-size: 17px;
    font-weight: 700;
}

.footer-column h4::after {
    content: "";

    position: absolute;
    left: 0;
    bottom: 0;

    width: 40px;
    height: 3px;

    background: #4da3ff;
    border-radius: 5px;
}


/* Links */

.footer-links {
    list-style: none;
    margin: 0;
    padding: 0;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    display: inline-flex;
    align-items: center;
    gap: 7px;

    color: #764AD9;
    text-decoration: none;

    font-size: 14px;

    transition:
        color 0.2s ease,
        transform 0.2s ease;
}

.footer-links a span {
    color: #A07EED;
    font-size: 18px;
}

.footer-links a:hover {
    color: #350F73;
    transform: translateX(4px);
}


/* Contact */

.contact-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.contact-list li {
    display: flex;
    align-items: flex-start;

    gap: 12px;

    margin-bottom: 15px;

    color: #764AD9;
    font-size: 14px;
    line-height: 1.7;
}

.contact-icon {
    min-width: 25px;
    font-size: 18px;
}


/* Bottom */

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.10);
    background: #9D6FF7;
}

.footer-bottom-content {
    min-height: 65px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    color: #ffffff;
    font-size: 12px;
}

.footer-agency {
    text-align: right;
}


/* Back To Top */

.back-to-top {
    position: fixed;

    right: 25px;
    bottom: 25px;

    width: 45px;
    height: 45px;

    border: none;
    border-radius: 50%;

    background: #1677d2;
    color: #ffffff;

    font-size: 20px;
    font-weight: bold;

    cursor: pointer;

    opacity: 0;
    visibility: hidden;

    transform: translateY(10px);

    transition:
        opacity 0.25s ease,
        transform 0.25s ease,
        visibility 0.25s ease;

    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.20);

    z-index: 999;
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background: #0b5cad;
}


/* ========================================
   RESPONSIVE
======================================== */

@media (max-width: 992px) {

    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 35px;
    }

}


@media (max-width: 600px) {

    .site-footer .container {
        width: min(100% - 30px, 1200px);
    }

    .footer-main {
        padding: 40px 0 30px;
    }

    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .footer-bottom-content {
        padding: 18px 0;

        flex-direction: column;
        align-items: flex-start;

        line-height: 1.7;
    }

    .footer-agency {
        text-align: left;
    }

    .back-to-top {
        right: 15px;
        bottom: 15px;
    }

}

</style>


<script>
/**
 * Back To Top
 */

(function () {

    const button = document.getElementById('backToTop');

    if (!button) return;

    window.addEventListener('scroll', function () {

        if (window.scrollY > 400) {
            button.classList.add('show');
        } else {
            button.classList.remove('show');
        }

    });

    button.addEventListener('click', function () {

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });

    });

})();
</script>