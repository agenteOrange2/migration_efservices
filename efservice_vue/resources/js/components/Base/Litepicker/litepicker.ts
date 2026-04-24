import dayjs from "dayjs";
import Litepicker from "litepicker";
import {
  type LitepickerElement,
  type LitepickerProps,
  type LitepickerEmit,
} from "./Litepicker.vue";

const getDateFormat = (format: string | undefined) => {
  return format !== undefined ? format : "D MMM, YYYY";
};

const setValue = (props: LitepickerProps, emit: LitepickerEmit) => {
  // Do not auto-emit today's date when modelValue is empty.
  // The input should remain blank until the user explicitly picks a date.
};

const init = (
  el: LitepickerElement,
  props: LitepickerProps,
  emit: LitepickerEmit
) => {
  const format = getDateFormat(props.options.format);
  el.litePickerInstance = new Litepicker({
    ...props.options,
    element: el,
    format: format,
    setup: (picker) => {
      if (picker.on) {
        picker.on("selected", (startDate, endDate) => {
          let date = dayjs(startDate.dateInstance).format(format);
          date +=
            endDate !== undefined && endDate !== null
              ? " - " + dayjs(endDate.dateInstance).format(format)
              : "";
          emit("update:modelValue", date);
        });
      }
    },
  });
};

const reInit = (
  el: LitepickerElement,
  props: LitepickerProps,
  emit: LitepickerEmit
) => {
  el.litePickerInstance.destroy();
  init(el, props, emit);
};

export { setValue, init, reInit };
