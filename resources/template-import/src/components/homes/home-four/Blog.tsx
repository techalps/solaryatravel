import Image from "next/image"
import blog_data from "@/data/BlogData"
import Link from "next/link"

import shape_1 from "@/assets/img/blog/blog-2/shape-2.png"
import shape_2 from "@/assets/img/blog/blog-2/shape.png"

const Blog = () => {
   return (
      <div className="tg-blog-area tg-blog-space-2 pt-130 p-relative z-index-1">
         <Image className="tg-blog-2-shape p-absolute d-none d-xl-block" src={shape_1} alt="" />
         <Image className="tg-blog-2-shape-1 p-absolute d-none d-xl-block" src={shape_2} alt="" />
         <div className="container">
            <div className="row">
               <div className="col-lg-12">
                  <div className="tg-location-section-title text-center mb-30">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".9s">Blog And Article</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">Latest News & Articles</h2>
                     <p className="text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Are you tired of the typical tourist destinations and<br /> looking
                        to step out of your comfort zonetravel</p>
                  </div>
               </div>
               {blog_data.filter((items) => items.page === "home_1").map((item) => (
                  <div key={item.id} className="col-xl-4 col-lg-6 col-md-6 wow fadeInLeft" data-wow-delay=".4s" data-wow-duration=".9s">
                     <div className="tg-blog-item tg-blog-2-item mb-25">
                        <div className="tg-blog-thumb p-relative fix mb-20">
                           <Link href="/blog-details"><Image className="w-100" src={item.thumb} alt="blog" /></Link>
                           <span className="tg-blog-tag p-absolute">{item.tag}</span>
                        </div>
                        <div className="tg-blog-content  p-relative">
                           <h3 className="tg-blog-title"><Link href="/blog-details">{item.title}</Link></h3>
                           <div className="tg-blog-date mb-10">
                              <span className="mr-20"><i className="fa-light fa-calendar"></i>{item.date}</span>
                              <span><i className="fa-regular fa-clock"></i>{item.time}</span>
                           </div>
                           <p className="tg-blog-text mb-0">{item.desc}</p>
                        </div>
                     </div>
                  </div>
               ))}
               <div className="col-12 wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">
                  <div className="tg-blog-bottom text-center pt-15">
                     <p>Want to see our Recent News & Updates. <a href="blog-grid.html">Click here to View More</a></p>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Blog
