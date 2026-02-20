
export interface KPIValue {
    value: number
    change?: number
    trend?: 'up' | 'down' | 'neutral'
    label?: string
}

export interface KPIs {
    farmers: {
        total_farmers: KPIValue
        total_offerings: KPIValue
    }
    dealers: {
        total_dealers: KPIValue
        total_demands: KPIValue
    }
    varieties: {
        total_varieties: KPIValue
        price_updates_week: KPIValue
        needs_attention: KPIValue
        average_harvest_time: KPIValue
    }
}