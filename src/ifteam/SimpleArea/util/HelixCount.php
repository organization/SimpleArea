<?php

namespace ifteam\SimpleArea\util;

class HelixCount {
    public static function helixIncrease(&$x, &$z) {
        if ($x >= 0 and $z >= 0) { // x+ x+
            if ($x == $z) { // 2:2 -> 3:2
                ++$x;
                return;
            }
            if (abs($x) > abs($z)) { // 3:2 -> 3:1
                --$z;
                return;
            } else { // 1:3 -> 2:3
                ++$x;
                return;
            }
        }
        if ($x >= 0 and $z <= 0) { // x+ z-
            if (($x * -1) == $z) { // 3:-3 -> 2:-3
                --$x;
                return;
            }
            if (abs($x) > abs($z)) { // 3:-1 -> 3:-2
                --$z;
                return;
            } else { // 2:-3 -> 1:-3
                --$x;
                return;
            }
        }
        if ($x <= 0 and $z <= 0) { // x- z-
            if ($x == $z) { // -3:-3 -> -3:-2
                ++$z;
                return;
            }
            if (abs($x) > abs($z)) { // -3:-2 -> -3:-1
                ++$z;
                return;
            } else { // -1:-3 -> -2:-3
                --$x;
                return;
            }
        }
        if ($x <= 0 and $z >= 0) { // x - z+
            if (($x * -1) == $z) { // -3:3 -> -2.3
                ++$x;
                return;
            }
            if (abs($x) > abs($z)) { // -3:1 -> -3.2
                ++$z;
                return;
            } else { // -2:3 -> -1:3
                ++$x;
                return;
            }
        }
    }

    public static function helixDecrease(&$x, &$z) {
        if ($x >= 0 and $z >= 0) { // x+ x+
            if ($x == $z) { // 2:2 -> 1:2
                --$x;
                return;
            }
            if (($x - 1) == $z) { // 3:2 -> 2:2
                --$x;
                return;
            }
            if (abs($x) > abs($z)) { // 3:2 -> 3:3
                ++$z;
                return;
            } else { // 1:3 -> 0:3
                --$x;
                return;
            }
        }
        if ($x >= 0 and $z <= 0) { // x+ z-
            if (($x * -1) == $z) { // 3:-3 -> 3:-2
                ++$z;
                return;
            }
            if (abs($x) > abs($z)) { // 3:-1 -> 3:0
                ++$z;
                return;
            } else { // 2:-3 -> 3:-3
                ++$x;
                return;
            }
        }
        if ($x <= 0 and $z <= 0) { // x- z-
            if ($x == $z) { // -3:-3 -> -2:-3
                ++$x;
                return;
            }
            if (abs($x) > abs($z)) { // -3:-2 -> -3:-3
                --$z;
                return;
            } else { // -1:-3 -> 0:-3
                ++$x;
                return;
            }
        }
        if ($x <= 0 and $z >= 0) { // x - z+
            if (($x * -1) == $z) { // -3:3 -> -3:2
                --$z;
                return;
            }
            if (abs($x) > abs($z)) { // -3:1 -> -3:0
                --$z;
                return;
            } else { // -2:3 -> -3:3
                --$x;
                return;
            }
        }
    }
}

?>