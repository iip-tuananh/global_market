@extends('layouts.main')

@section('css')
<link rel="stylesheet" href="{{ asset('libs/pagination/pagination.css') }}">
@endsection

@section('title')
    Báo cáo tưởng hoa hồng
@endsection

@section('content')
<div ng-controller="RevenueReport" ng-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Bộ lọc</h4>
                </div>
                <div class="card-body">
                    <div class="row">
						<div class="col-md-3">
                            <div class="form-group custom-group">
                                <label>Từ ngày:</label>
                                <input class="form-control" date-form ng-model="form.from_date" theme="select2">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group custom-group">
                                <label>Đến ngày:</label>
                                <input class="form-control" date-form ng-model="form.to_date" theme="select2">
                            </div>
                        </div>
						<div class="col-md-3">
							<div class="form-group custom-group">
                                <label>Người dùng</label>
                                <ui-select remove-selected="false" ng-model="form.user_id" theme="select2">
									<ui-select-match placeholder="Chọn Khách hàng">
										<% $select.selected.name %>
									</ui-select-match>
									<ui-select-choices repeat="item.id as item in (users | filter: $select.search)">
										<span ng-bind="item.name"></span>
									</ui-select-choices>
								</ui-select>
                            </div>
						</div>
                    </div>
                    <hr>
                    <div class="text-right">

                        <button class="btn btn-primary" ng-click="filter(1)" ng-disabled="loading.search">
                            <i ng-if="!loading.search" class="fa fa-filter"></i>
                            <i ng-if="loading.search" class="fa fa-spinner fa-spin"></i>
                            Lọc
                        </button>
						{{-- <a href="<% printURL() %>" target="_blank" class="btn btn-info text-light" ng-disabled="loading.search">
                            <i ng-if="!loading.search" class="fa fa-print"></i>
                            <i ng-if="loading.search" class="fa fa-spinner fa-spin"></i>
                            In
                        </a> --}}
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h4>Chi tiết</h4>
                </div>
                <div class="card-body">
                    <table class="table table-condensed table-bordered table-head-border">
						<thead class="sticky-thead">
							<tr>
								<th>STT</th>
                                <th>Họ tên</th>
								<th>Số điện thoại</th>
                                <th>Email</th>
								<th>Tổng hoa hồng chờ xử lý</th>
								<th>Tổng hoa hồng chờ quyết toán</th>
								<th>Tổng hoa hồng đã quyết toán</th>
								<th>Tổng hoa hồng</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-if="loading.search">
								<td colspan="8"><i class="fa fa-spin fa-spinner"></i> Đang tải dữ liệu</td>
							</tr>
							<tr ng-if="!loading.search && details && !details.length">
								<td colspan="8">Chưa có dữ liệu</td>
							</tr>
                            <tr ng-if="!loading.search && details && details.length">
								<td class="text-center" colspan="4"><b>Tổng cộng</b></td>
								<td class="text-right"><b><% (summary.total_amount_pending ? (summary.total_amount_pending | number) : '-') %></b></td>
								<td class="text-right"><b><% (summary.total_amount_wait_payment ? (summary.total_amount_wait_payment | number) : '-') %></b></td>
								<td class="text-right"><b><% (summary.total_amount_paid ? (summary.total_amount_paid | number) : '-') %></b></td>
								<td class="text-right"><b><% (summary.total_amount ? (summary.total_amount | number) : '-') %></b></td>
							</tr>
							<tr ng-if="!loading.search && details && details.length" ng-repeat="d in details">
								<td class="text-center"><% $index + 1 + (current.page - 1) * per_page %></td>
                                <td><% d.name %></td>
								<td><% d.phone_number %></td>
								<td><% d.email %></td>
								<td class="text-right"><% (d.total_amount_pending ? (d.total_amount_pending | number) : '-') %></td>
								<td class="text-right"><% (d.total_amount_wait_payment ? (d.total_amount_wait_payment | number) : '-') %></td>
								<td class="text-right"><% (d.total_amount_paid ? (d.total_amount_paid | number) : '-') %></td>
								<td class="text-right"><% (d.total_amount ? (d.total_amount | number) : '-') %></td>
							</tr>
						</tbody>
					</table>
					<div class="text-right mt-2">
						<ul uib-pagination ng-change="pageChanged()" total-items="total_items" ng-model="current.page" max-size="10"
							class="pagination-sm" boundary-links="true" items-per-page="per_page" previous-text="‹" next-text="›" first-text="«" last-text="»">
						</ul>
					</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('libs/pagination/ui-bootstrap.min.js') }}"></script>
<script>
    angular.module("App").requires.push('ui.bootstrap');
    app.controller('RevenueReport', function ($scope) {
        $scope.form = {};
		$scope.details = [];
		$scope.users = @json(App\Model\Common\User::getForSelectUserClients());

        let draw = 0;
        $scope.current = {
            page: 1
        };
        $scope.per_page = 10;
        $scope.total_items = 0;
        $scope.loading = {
            search: false
        };
        $scope.summary = {};

		$scope.filter = function(page = 1) {
			draw++;
			$scope.current.page = page;
			$scope.loading.search = true;
			$.ajax({
                type: 'GET',
                url: "{{ route('Report.revenueReportSearchData') }}",
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                data: {
					...$scope.form,
					per_page: $scope.per_page,
					current_page: $scope.current.page,
					draw: draw
				},
                success: function(response) {
                    if (response.success && response.draw == draw) {
						$scope.details = response.data.data;
                        $scope.details.map(d => {
                            d.total_amount = Number(d.total_amount_pending) + Number(d.total_amount_wait_payment) + Number(d.total_amount_paid);
                            return d;
                        });
						$scope.total_items = response.data.total;
						$scope.current.page = response.data.current_page;
						$scope.summary = {
                            total_amount_pending: $scope.details.reduce((sum, d) => sum + Number(d.total_amount_pending), 0),
                            total_amount_wait_payment: $scope.details.reduce((sum, d) => sum + Number(d.total_amount_wait_payment), 0),
                            total_amount_paid: $scope.details.reduce((sum, d) => sum + Number(d.total_amount_paid), 0),
                            total_amount: $scope.details.reduce((sum, d) => sum + Number(d.total_amount), 0)
                        };
					}
				},
				error: function(err) {
					toastr.error('Đã có lỗi xảy ra');
				},
				complete: function() {
					$scope.loading.search = false;
					$scope.$applyAsync();
				}
            });
        }

		$scope.filter(1);

		$scope.pageChanged = function() {
			$scope.filter($scope.current.page);
		};

		// function getFilterParams() {
		// 	return $.param($scope.form);
		// }

		// $scope.printURL = function() {
        //     return `{{ route('Report.promoReportPrint') }}?${getFilterParams()}`;
        // }
    })
</script>
@endsection
