<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 *
 * Faux conditional class definition as this is common with module checks
 */

if (true) {
    class Le_Foo_Le_Bar_R1 extends Le_Foo_Le_Bar_R2
    {
    }
} else {
    class Le_Foo_Le_Bar_R1 extends Le_Foo_Le_Bar_Nexiste_Pas
    {
    }
}
