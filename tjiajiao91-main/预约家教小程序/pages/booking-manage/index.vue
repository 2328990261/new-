<template>
	<view class="booking-manage-container">
		<!-- 自定义导航栏 -->
		<view class="custom-navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
			<view class="navbar-content">
				<text class="navbar-title">预约管理</text>
			</view>
		</view>
		
		<!-- 内容区域 -->
		<scroll-view class="content-scroll" scroll-y :style="{ paddingTop: navbarHeight + 'px' }">
			<!-- 状态筛选 -->
			<view class="status-tabs">
				<view 
					v-for="(item, index) in statusList" 
					:key="index"
					class="status-tab"
					:class="{ active: currentStatus === item.value }"
					@click="changeStatus(item.value)"
				>
					<text>{{ item.label }}</text>
					<view v-if="item.count > 0" class="badge">{{ item.count }}</view>
				</view>
			</view>
			
			<!-- 订单列表 -->
			<view class="order-list">
				<view v-if="loading" class="loading-wrapper">
					<text>加载中...</text>
				</view>
				
				<view v-else-if="orderList.length === 0" class="empty-wrapper">
					<text class="empty-icon">📋</text>
					<text class="empty-text">暂无预约记录</text>
					<button class="go-booking-btn" @click="goBooking">立即预约</button>
				</view>
				
				<view v-else class="order-items">
					<view 
				