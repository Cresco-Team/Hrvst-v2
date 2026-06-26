declare namespace App {
namespace Data {
namespace Category {
export type CategoryData = {
id: number,
name: string,
slug: string,
};
}
namespace Dealer {
export type DealerExpiringDemandData = {
id: number,
scheduled_date: string | null,
time_slot: App.Enums.PostTimeSlot | null,
time_slot_label: string | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: undefined<number, App.Data.PostItem.PostItemLightData> | undefined,
};
}
namespace Farmer {
export type FarmerExpiringSupplyData = {
id: number,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: undefined<number, App.Data.PostItem.PostItemLightData> | undefined,
};
}
namespace Post {
export type DealerDemandData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
post_items: undefined<number, App.Data.PostItem.PostItemLightData>,
};
export type FarmerSupplyData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
post_items: undefined<number, App.Data.PostItem.PostItemLightData>,
};
export type PostData = {
id: number,
type: App.Enums.PostType,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
days_until_transaction: number | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: undefined<number, App.Data.PostItem.PostItemData> | undefined,
};
}
namespace PostItem {
export type PostItemData = {
id: number,
image_url: string,
name: string,
post_id: number,
status: App.Enums.PostItemStatus,
variety: App.Data.Variety.VarietyData | undefined,
variety_id: number,
variety_name: string,
vegetable_id: number,
vegetable_name: string,
category_name: string,
quantity_kg: number,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
days_until_transaction: number | null,
created_at: string,
created_at_human: string,
};
export type PostItemLightData = {
id: number,
variety_id: number,
variety_name: string | null,
vegetable_name: string | null,
vegetable_image_url: string | null,
quantity_kg: number,
status: App.Enums.PostItemStatus | null,
};
}
namespace Profile {
export type CoordinatesData = {
lat: number,
lng: number,
};
export type DealerData = {
id: number,
joined_at: string,
joined_at_human: string,
user: App.Data.Profile.UserData | undefined,
ongoing_demands_count: undefined | number,
demands: undefined<number, App.Data.PostItem.PostItemLightData> | undefined,
demand_items: undefined<number, App.Data.PostItem.PostItemData> | undefined,
};
export type FarmerData = {
id: number,
joined_at: string,
joined_at_human: string,
user: App.Data.Profile.UserData | undefined,
full_address: string,
barangay: undefined | string | null,
municipality: undefined | string | null,
province: undefined | string | null,
coordinates: App.Data.Profile.CoordinatesData,
ongoing_supplies_count: undefined | number,
supplies: undefined<number, App.Data.PostItem.PostItemLightData> | undefined,
supply_items: undefined<number, App.Data.PostItem.PostItemData> | undefined,
};
export type UserData = {
id: number,
name: string,
email: string | null,
phone_number: string,
avatar_url: string,
};
}
namespace Variety {
export type VarietyData = {
id: number,
name: string,
vegetable: App.Data.Vegetable.VegetableData | undefined,
supply_count: undefined | number,
demand_count: undefined | number,
};
export type VarietyDetailData = {
id: number,
name: string,
vegetable_id: number,
vegetable_name: string,
category_id: number,
category_name: string,
category_image_url: string,
supply_count: number,
demand_count: number,
supply_municipalities: Array<any>,
monthly_activity: Array<any>,
variety_calendar: Array<any>,
analytics: Array<any> | null,
};
export type VarietyLightData = {
id: number,
name: string,
};
}
namespace Vegetable {
export type VarietyAdminRowData = {
id: number,
name: string,
is_variety: boolean,
supply_count: number,
demand_count: number,
};
export type VarietyCountData = {
id: number,
name: string,
supply_count: number,
demand_count: number,
};
export type VegetableAdminData = {
id: number,
name: string,
is_variety: boolean,
image_url: string,
varieties_count: undefined | number,
category: App.Data.Category.CategoryData | undefined,
varieties: undefined<number, App.Data.Vegetable.VarietyAdminRowData> | undefined,
};
export type VegetableData = {
id: number,
name: string,
image_url: string,
varieties_count: undefined | number,
category: App.Data.Category.CategoryData | undefined,
varieties: undefined<number, App.Data.Variety.VarietyData> | undefined,
};
export type VegetableLightData = {
id: number,
name: string,
category: string | null,
image_url: string | null,
};
export type VegetableSharedData = {
id: number,
name: string,
image_url: string,
varieties_count: undefined | number,
category: App.Data.Category.CategoryData | undefined,
varieties: undefined<number, App.Data.Vegetable.VarietyCountData> | undefined,
};
}
}
namespace Enums {
export type PostItemStatus = 'ongoing' | 'fulfilled' | 'expired';
export type PostTimeSlot = 'morning' | 'afternoon' | 'evening';
export type PostType = 'supply' | 'demand';
namespace Analytics {
export type ImbalanceBand = 'oversupply' | 'balanced' | 'undersupply';
export type RecommendationSeverity = 'critical' | 'warning' | 'info';
export type VarietyViewerRole = 'admin' | 'marketplace';
}
}
}
