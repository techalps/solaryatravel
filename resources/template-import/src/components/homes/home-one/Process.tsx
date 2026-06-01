import Image, { StaticImageData } from "next/image";
import { JSX } from "react";
import Link from "next/link";
import ProcessOne from "@/svg/home-one/ProcessOne";
import ProcessTwo from "@/svg/home-one/ProcessTwo";
import ProcessThree from "@/svg/home-one/ProcessThree";

import process_1 from "@/assets/img/chose/chose-2/thumb-1.jpg"
import process_2 from "@/assets/img/chose/chose-2/thumb-2.jpg"
import process_3 from "@/assets/img/chose/chose-2/thumb-3.jpg"
import shape from "@/assets/img/chose/chose-2/shape.png"

interface DataType {
   id: number;
   thumb?: StaticImageData;
   icon?: JSX.Element;
   title?: string;
   desc?: string;
}

const process_data: DataType[] = [
   {
      id: 1,
      thumb: process_1,
   },
   {
      id: 2,
      icon: (<><ProcessOne /></>),
      title: "Best Travel Agency",
      desc: "Are you tired offer theare typical tourist new destination"
   },
   {
      id: 3,
      thumb: process_2,
   },
   {
      id: 4,
      icon: (<><ProcessTwo /></>),
      title: "Secure Journey With Us",
      desc: "Are you tired offer theare typical tourist new destination"
   },
   {
      id: 5,
      thumb: process_3,
   },
   {
      id: 6,
      icon: (<><ProcessThree /></>),
      title: "Top Class Places",
      desc: "Are you tired offer theare typical tourist new destination"
   },
];

const Process = () => {
   return (
      <div className="tg-chose-area tg-chose-su-wrap pt-100 pb-105 p-relative z-index-9">
         <Image className="tg-chose-2-shape d-none d-lg-block" src={shape} alt="shape" />
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-lg-6 col-md-8">
                  <div className="tg-chose-section-title text-center mb-40">
                     <h5 className="tg-section-su-subtitle su-subtitle-2 mb-20 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">our Working Process</h5>
                     <h2 className="tg-section-su-title text-none wow fadeInUp mb-15" data-wow-delay=".5s" data-wow-duration=".9s">See how we work step by step</h2>
                     <p className="tg-section-su-para tg-section-su-para-2 mb-10">Are you tired of the typical tourist destinations and areabetter looking<br />
                        to step out of your comfort zonetravel</p>
                  </div>
               </div>
            </div>
            <div className="row">
               {process_data.map((item) => (
                  item.thumb ? (
                     <div key={item.id} className="col-lg-4 col-md-6 col-sm-6 mb-25">
                        <div className="tg-chose-2-thumb h-100 wow fadeInLeft" data-wow-delay=".4s" data-wow-duration=".6s">
                           <Image className="w-100 h-100" src={item.thumb} alt="chose" />
                        </div>
                     </div>
                  ) : (
                     <div key={item.id} className="col-lg-4 col-md-6 col-sm-6 mb-25">
                        <div className="tg-chose-2-content p-relative text-center z-index-1 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".6s">
                           <span className="tg-chose-2-box-shape">
                              <svg width="62" height="57" viewBox="0 0 62 57" fill="none" xmlns="http://www.w3.org/2000/svg">
                                 <path d="M83.0149 6.0725L59.8978 44.6152M83.0149 6.0725L44.4722 29.1896M83.0149 6.0725L48.8307 40.2567M59.8978 44.6152L83.0149 83.158M59.8978 44.6152L62.248 48.7173L79.2974 44.6152L62.3762 40.3849L59.8978 44.6152ZM59.8978 44.6152L50.7108 44.6152M83.0149 83.158L44.4722 60.0409M83.0149 83.158L48.8307 48.9737M44.4722 60.0409L5.92945 83.158M44.4722 60.0409L48.5743 62.391L44.4722 79.4404L40.2419 62.5192L44.4722 60.0409ZM44.4722 60.0409L44.4722 50.8539M5.92945 83.158L29.0465 44.6152M5.92945 83.158L40.1137 48.9737M29.0465 44.6152L5.92944 6.07251M29.0465 44.6152L26.5682 40.3849L9.64698 44.6152L26.5682 48.8455L29.0465 44.6152ZM29.0465 44.6152L38.2335 44.6152M5.92944 6.07251L44.4722 29.1896M5.92944 6.07251L40.1137 40.2567M44.4722 29.1896L40.2419 26.7112L44.4722 9.79004L48.7025 26.7112L44.4722 29.1896ZM44.4722 29.1896L44.4722 38.3766M48.8307 40.2567C51.2236 42.6496 51.2449 46.5595 48.8307 48.9737M48.8307 40.2567C46.4378 37.8638 42.5066 37.8638 40.1137 40.2567M48.8307 48.9737C46.4378 51.3666 42.5279 51.388 40.1137 48.9737M40.1137 48.9737C37.7208 46.5808 37.6994 42.671 40.1137 40.2567M8.92057 67.9887C-0.394638 53.9304 -0.266448 35.4282 8.92056 21.2418M67.8456 80.1668C53.7874 89.482 35.2852 89.3539 21.0987 80.1668M80.0238 21.2418C89.339 35.3 89.2108 53.8022 80.0238 67.9887M21.0987 9.06363C35.157 -0.251579 53.6592 -0.123389 67.8456 9.06362" stroke="#E8E4F0" strokeWidth="3.33289" strokeMiterlimit="10" strokeLinecap="round" strokeLinejoin="round" />
                              </svg>
                           </span>
                           <div className="tg-chose-2-icon mb-20">
                              {item.icon}
                           </div>
                           <h4 className="tg-chose-2-title mb-15"><Link href="/contact">{item.title}</Link></h4>
                           <p>{item.desc}</p>
                        </div>
                     </div>
                  )
               ))}
            </div>
         </div>
      </div>
   )
}

export default Process
