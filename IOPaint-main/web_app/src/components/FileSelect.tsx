import { useState } from "react"
import useResolution from "@/hooks/useResolution"

type FileSelectProps = {
  onSelection: (file: File) => void
  onBatchSelection?: (files: File[]) => void
}

export default function FileSelect(props: FileSelectProps) {
  const { onSelection, onBatchSelection } = props

  const [uploadElemId] = useState(`file-upload-${Math.random().toString()}`)
  const [batchUploadElemId] = useState(
    `file-upload-batch-${Math.random().toString()}`
  )

  const resolution = useResolution()

  function onFileSelected(file: File) {
    if (!file) {
      return
    }
    // Skip non-image files
    const isImage = file.type.match("image.*")
    if (!isImage) {
      return
    }
    try {
      // Check if file is larger than 20mb
      if (file.size > 20 * 1024 * 1024) {
        throw new Error("file too large")
      }
      onSelection(file)
    } catch (e) {
      // eslint-disable-next-line
      alert(`error: ${(e as any).message}`)
    }
  }

  function onFilesSelected(files: File[]) {
    const filtered = files.filter((f) => f.type.match("image.*"))
    if (filtered.length === 0) {
      return
    }
    if (!onBatchSelection || filtered.length === 1) {
      onFileSelected(filtered[0])
      return
    }
    onBatchSelection(filtered)
  }

  return (
    <div className="absolute flex w-screen h-screen justify-center items-center pointer-events-none">
      <div className="grid gap-3 pointer-events-auto">
        <label
          htmlFor={uploadElemId}
          className="grid bg-background border-[2px] border-[dashed] rounded-lg min-w-[600px] hover:bg-primary hover:text-primary-foreground"
        >
          <div
            className="grid p-16 w-full h-full"
            onDragOver={(ev) => {
              ev.stopPropagation()
              ev.preventDefault()
            }}
          >
            <input
              className="hidden"
              id={uploadElemId}
              name={uploadElemId}
              type="file"
              onChange={(ev) => {
                const files = Array.from(ev.currentTarget.files ?? [])
                onFilesSelected(files)
                ev.currentTarget.value = ""
              }}
              accept="image/png, image/jpeg"
            />
            <p className="text-center">
              {resolution === "desktop"
                ? "Click here or drag an image file"
                : "Tap here to load your picture"}
            </p>
          </div>
        </label>

        <label
          htmlFor={batchUploadElemId}
          className="text-center text-sm text-muted-foreground hover:text-foreground select-none"
        >
          批量上传图片
          <input
            className="hidden"
            id={batchUploadElemId}
            name={batchUploadElemId}
            type="file"
            multiple
            onChange={(ev) => {
              const files = Array.from(ev.currentTarget.files ?? [])
              onFilesSelected(files)
              ev.currentTarget.value = ""
            }}
            accept="image/png, image/jpeg"
          />
        </label>
      </div>
    </div>
  )
}
