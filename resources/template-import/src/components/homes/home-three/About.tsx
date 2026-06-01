import Image from 'next/image'
import Link from 'next/link'
import Button from '@/components/common/Button'

import about_thumb1 from "@/assets/img/about/about.jpg"
import about_thumb2 from "@/assets/img/about/about-2.jpg"
import about_thumb3 from "@/assets/img/about/about-3.jpg"
import about_thumb4 from "@/assets/img/about/about-4.jpg"
import about_logo from "@/assets/img/about/logo.png"

const About = () => {
   return (
      <div className="tg-about-area pb-100">
         <div className="container">
            <div className="row">
               <div className="col-lg-3">
                  <div className="tg-about-thumb-wrap mb-30">
                     <Image className="w-100 tg-round-15 mb-85 wow fadeInLeft" data-wow-delay=".3s" data-wow-duration=".7s" src={about_thumb1} alt="about" />
                     <Image className="tg-about-thumb-2 tg-round-15 wow fadeInLeft" data-wow-delay=".4s" data-wow-duration=".9s" src={about_thumb2} alt="about" />
                  </div>
               </div>
               <div className="col-lg-6 mb-30">
                  <div className="tg-about-content text-center">
                     <div className="tg-about-logo mb-30 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".5s">
                        <Image src={about_logo} alt="logo" />
                     </div>
                     <div className="tg-about-section-title mb-25">
                        <h5 className="tg-section-subtitle wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".6s">Most Popular Tour</h5>
                        <h2 className="mb-15 wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".7s">Let’s Discover The World With Our Excellent Eyes</h2>
                        <p className="text-none wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".8s">Whether you&apos;re looking for a romantic getawaamily-friendly
                           solo journey to explore the world, a travel agency can provide
                           tailored itinerary that exceeds your expectations.</p>
                     </div>
                     <div className="tp-about-btn-wrap wow fadeInUp" data-wow-delay=".7s" data-wow-duration=".9s">
                        <Link href="/tour-details" className="tg-btn tg-btn-transparent tg-btn-switch-animation">
                           <Button text="Take a Tour" />
                        </Link>
                     </div>
                  </div>
               </div>
               <div className="col-lg-3">
                  <div className="tg-about-thumb-wrap  mb-30">
                     <Image className="w-100 tg-round-15 mb-85 wow fadeInRight" data-wow-delay=".3s" data-wow-duration=".7s" src={about_thumb3} alt="about" />
                     <Image className="tg-about-thumb-4 tg-round-15 wow fadeInRight" data-wow-delay=".4s" data-wow-duration=".9s" src={about_thumb4} alt="about" />
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default About
