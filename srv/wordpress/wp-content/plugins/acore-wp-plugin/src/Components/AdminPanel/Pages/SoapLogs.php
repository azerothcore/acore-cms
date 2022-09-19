<?php
    use ACore\Manager\Opts;
    // Now display the settings editing screen
    $myCredConfs = get_option('mycred_pref_core');
?>

<div class="wrap">
    <h2> <?= __('Soap Logs', Opts::I()->page_alias)?> </h2>
    <p>A list of the logged commands.</p>
    <div class="row">
        <div class="col">
            <div class="card p-0">
                <div class="card-body">
                    <h5>Soap Logs</h5>
                    <hr>
                    <form class="row row-cols-lg-auto g-2" url="<?php echo menu_page_url(ACORE_SLUG . '-soap-logs'); ?>">
                        <input type="hidden" value="<?php echo ACORE_SLUG . '-soap-logs'; ?>" name="page">
                        <div class="col">
                            <label class="visually-hidden" for="username">Username</label>
                            <input type="text" class="form-control form-control-sm" id="username" name="username" placeholder="Username" value="<?php echo $data['username']; ?>">
                        </div>
                        <div class="col">
                            <label class="visually-hidden" for="order_id">Order ID</label>
                            <input type="text" class="form-control form-control-sm" id="order_id" name="order_id" placeholder="Order ID" value="<?php echo $data['order_id']; ?>">
                        </div>

                        <div class="col">
                            <label class="visually-hidden" for="items">Items</label>
                            <select class="form-select" name="items" id="items">
                                <option value="10">10</option>
                                <option value="25" <?php echo $data['items'] == '25' ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $data['items'] == '50' ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $data['items'] == '100' ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>

                        <div class="col">
                            <button type="submit" class="btn btn-primary btn-sm">Search</button>
                        </div>

                        <div class="col ms-auto">
                            <nav aria-label="Table pagination">
                                <ul class="pagination pagination-sm">
                                    <?php
                                        $maxPagination = 3;
                                        $diff = $data["max_page"] - $data["pos"];
                                        $start = $data["pos"] - $maxPagination > 0 && $diff >= 0 ? $data["pos"] - $maxPagination : 1;
                                        $end = $data["pos"] + $maxPagination < $data["max_page"] && $diff > 0 ? $data["pos"] + $maxPagination : $data["max_page"];

                                        $link = menu_page_url(ACORE_SLUG . '-soap-logs', false);
                                        if ($data["username"]) {
                                            $link .= "&username" . $data["username"];
                                        }
                                        if ($data["order_id"]) {
                                            $link .= "&order_id" . $data["order_id"];
                                        }
                                        ?>

                                        <li class="page-item disabled">
                                        <?php if ($data["count"] > 0): ?>
                                        <a class="page-link">Results <?php echo ($data["pos"] - 1) * $data["items"] + 1; ?> to <?php echo $data["pos"] != $data["max_page"] ? $data["pos"] * $data["items"] : $data["count"]; ?> from <?php echo $data["count"]; ?> </a>
                                        <?php else: ?>
                                            <a class="page-link">No results </a>
                                        <?php endif; ?>
                                        </li>

                                        <?php
                                        $link .= "&items=" . $data["items"];
                                        if ($start > 1) {
                                            echo "<li class=\"page-item\"><a class=\"page-link\" href=\"$link&pos=1\">1</a></li>";
                                            if ($maxPagination <= $start) {
                                                echo "<li class=\"page-item disabled\"><a class=\"page-link\">...</a></li>";
                                            }
                                        }
                                        for ($i = $start; $i <= $end; $i++) {
                                            $href = "$link&pos=$i";
                                            $class = "";
                                            if ($i == $data["pos"]) {
                                                $href = "#";
                                                $class = " active";
                                            }
                                            echo "<li class=\"page-item{$class}\"><a class=\"page-link\" href=\"{$href}\">$i</a></li>";
                                        }
                                        if ($end < $data["max_page"]) {
                                            if ($data["max_page"] - 1 != $end) {
                                                echo "<li class=\"page-item disabled\"><a class=\"page-link\">...</a></li>";
                                            }
                                            echo "<li class=\"page-item\"><a class=\"page-link\" href=\"$link&pos={$data["max_page"]}\">{$data["max_page"]}</a></li>";
                                        }
                                    ?>
                                </ul>
                            </nav>
                        </div>

                    </form>
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Result</th>
                                <th>Details</th>
                                <th>Command</th>
                                <th>User</th>
                                <th>Order</th>
                                <th>Executed DateTime</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($result as $item) {
                                echo "<tr><td>{$item->id}</td>";
                                if ($item->success) {
                                    echo "<td><span class=\"dashicons dashicons-yes text-success\"></span></td>";
                                } else {
                                    echo "<td><span class=\"dashicons dashicons-no text-danger\"></span></td>";
                                }
                                echo "<td>{$item->result}</td>";
                                echo "<td>{$item->command}</td>";
                                $user_info = get_userdata($item->user_id);
                                echo "<td><a href=\"" . get_edit_user_link($item->user_id) . "\">{$user_info->user_login}</a></td>";
                                echo "<td>{$item->order_id}</td>";
                                echo "<td>{$item->executed_at}</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <hr />
    <script>
        jQuery('#preview').on('click', function (e) {
            jQuery('#pvp-rewards').attr('method', 'GET');
        });
        jQuery('#pvp-rewards').on('submit', function (e) {
            return confirm("You sure you want to continue?");
        });
        jQuery(document).on('ready', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</div>
