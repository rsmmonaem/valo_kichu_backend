import React from 'react'
import CategoryTemplate from '../common/CategoryTemplate'

function SubCategories() {
    console.log("called sub sub");
  return (
    <div>
      <CategoryTemplate pageTitle={"Sub Category"} buttonName={"Add Sub Category"} />
    </div>
  )
}

export default SubCategories
