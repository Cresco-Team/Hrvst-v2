declare namespace App {
namespace Data {
namespace Post {
export type FarmerSupplyData = {
id: number,
user_id: number,
vegetable_id: number,
type: App.Enums.PostType,
status: App.Enums.PostStatus,
expected_harvest_month: string | null,
scheduled_date: string | null,
scheduled_date_human: string | null,
time_slot: App.Enums.PostTimeSlot,
estimated_total_weight: number,
created_at: string,
created_at_human: string,
vegetable: App.Data.Vegetable.VegetableLightData | null,
post_items: undefined<number, App.Data.PostItem.PostItemLightData>,
item: App.Data.PostItem.PostItemLightData,
};
}
namespace PostItem {
export type PostItemLightData = {
id: number,
variety_id: number,
variety_name: string | null,
quantity_kg: number,
status: App.Enums.PostItemStatus,
};
}
namespace Vegetable {
export type VegetableLightData = {
id: number,
name: string,
category: string | null,
image_url: string | null,
};
}
}
namespace Enums {
export type PostItemStatus = 'ongoing' | 'fulfilled' | 'unsettled';
export type PostStatus = 'growing' | 'ready for harvest';
export type PostTimeSlot = 'morning' | 'afternoon' | 'evening';
export type PostType = 'supply' | 'demand';
namespace Analytics {
export type ImbalanceBand = 'oversupply' | 'balanced' | 'undersupply';
export type RecommendationSeverity = 'critical' | 'warning' | 'info';
export type VarietyViewerRole = 'admin' | 'marketplace';
}
}
}
